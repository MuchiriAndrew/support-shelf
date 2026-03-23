<?php

namespace App\Services\Documents;

use App\Jobs\DeleteChunkVectorsJob;
use App\Jobs\SyncDocumentVectorsJob;
use App\Models\Document;
use App\Models\Source;
use App\Support\SupportActivityLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentIngestionService
{
    public function __construct(
        protected DocumentNormalizer $normalizer,
        protected ChunkingService $chunkingService,
        protected TokenEstimator $tokenEstimator,
    ) {
    }

    /**
     * Normalize and persist a document and its chunks.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{document: Document, created: bool, updated: bool, chunks_count: int}
     */
    public function ingestText(?Source $source, string $title, string $documentType, string $content, array $attributes = []): array
    {
        SupportActivityLog::info('Document ingestion started', [
            'source_id' => $source?->id,
            'source_name' => $source?->name,
            'document_title' => $title,
            'document_type' => $documentType,
            'content_length' => mb_strlen($content),
            'canonical_url' => $attributes['canonical_url'] ?? null,
            'storage_disk' => $attributes['storage_disk'] ?? null,
            'storage_path' => $attributes['storage_path'] ?? null,
        ]);

        $normalizedText = $this->normalizer->normalize($content);

        if (mb_strlen($normalizedText) < 40) {
            SupportActivityLog::warning('Document ingestion skipped because normalized content was too short', [
                'source_id' => $source?->id,
                'document_title' => $title,
                'normalized_length' => mb_strlen($normalizedText),
                'canonical_url' => $attributes['canonical_url'] ?? null,
            ]);

            throw new InvalidArgumentException('The extracted document content was too short to store.');
        }

        $checksum = sha1($normalizedText);
        $tokenEstimate = $this->tokenEstimator->estimate($normalizedText);
        $document = $this->findExistingDocument($source, $attributes, $checksum);
        $vectorIdsToPrune = [];
        $chunks = $this->chunkingService->chunk($normalizedText);

        $created = ! $document;
        $updated = true;

        if ($document && $document->checksum === $checksum) {
            $document->fill([
                'title' => $title,
                'document_type' => $documentType,
                'language' => (string) ($attributes['language'] ?? 'en'),
                'token_estimate' => $tokenEstimate,
                'status' => 'ready',
                'metadata' => $this->mergeMetadata($document->metadata, $attributes['metadata'] ?? []),
            ])->save();

            if ($this->shouldQueueVectorSync($document)) {
                SyncDocumentVectorsJob::dispatch($document->id)->afterCommit();

                SupportActivityLog::info('Vector sync queued for unchanged document', [
                    'document_id' => $document->id,
                    'document_title' => $document->title,
                ]);
            }

            SupportActivityLog::info('Document ingestion completed without content changes', [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'token_estimate' => $tokenEstimate,
                'chunks_count' => $document->chunks()->count(),
            ]);

            return [
                'document' => $document->load('chunks'),
                'created' => false,
                'updated' => false,
                'chunks_count' => $document->chunks()->count(),
            ];
        }

        $document ??= new Document();
        $vectorIdsToPrune = $document->exists
            ? $document->chunks()->whereNotNull('vector_id')->pluck('vector_id')->filter()->values()->all()
            : [];

        DB::transaction(function () use ($document, $source, $title, $documentType, $normalizedText, $checksum, $tokenEstimate, $attributes, $chunks): void {
            $document->fill([
                'source_id' => $source?->id,
                'title' => $title,
                'document_type' => $documentType,
                'language' => (string) ($attributes['language'] ?? 'en'),
                'storage_disk' => $attributes['storage_disk'] ?? null,
                'storage_path' => $attributes['storage_path'] ?? null,
                'canonical_url' => $attributes['canonical_url'] ?? null,
                'checksum' => $checksum,
                'content_text' => $normalizedText,
                'token_estimate' => $tokenEstimate,
                'status' => 'ready',
                'metadata' => $this->mergeMetadata($document->metadata, $attributes['metadata'] ?? []),
            ]);
            $document->save();

            $document->chunks()->delete();

            foreach ($chunks as $index => $chunk) {
                $document->chunks()->create([
                    'chunk_index' => $index,
                    'content' => $chunk['content'],
                    'token_estimate' => $chunk['token_estimate'],
                    'metadata' => [
                        'document_title' => $title,
                        'document_type' => $documentType,
                    ],
                ]);
            }
        });

        if ($vectorIdsToPrune !== [] && $this->shouldQueueVectorPrune()) {
            DeleteChunkVectorsJob::dispatch($vectorIdsToPrune)->afterCommit();

            SupportActivityLog::info('Stale vectors queued for deletion after document refresh', [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'vector_ids_count' => count($vectorIdsToPrune),
            ]);
        }

        if ($this->shouldQueueVectorSync($document)) {
            SyncDocumentVectorsJob::dispatch($document->id)->afterCommit();

            SupportActivityLog::info('Vector sync queued for ingested document', [
                'document_id' => $document->id,
                'document_title' => $document->title,
                'chunks_count' => count($chunks),
            ]);
        }

        SupportActivityLog::info('Document ingestion completed', [
            'document_id' => $document->id,
            'document_title' => $document->title,
            'source_id' => $source?->id,
            'created' => $created,
            'updated' => $updated,
            'chunks_count' => count($chunks),
            'token_estimate' => $tokenEstimate,
            'normalized_length' => mb_strlen($normalizedText),
        ]);

        return [
            'document' => $document->load('chunks'),
            'created' => $created,
            'updated' => $updated,
            'chunks_count' => $document->chunks()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function findExistingDocument(?Source $source, array $attributes, string $checksum): ?Document
    {
        $canonicalUrl = $attributes['canonical_url'] ?? null;

        if (is_string($canonicalUrl) && $canonicalUrl !== '') {
            return Document::query()
                ->when($source, fn ($query) => $query->where('source_id', $source->id))
                ->where('canonical_url', $canonicalUrl)
                ->first();
        }

        $storageDisk = $attributes['storage_disk'] ?? null;
        $storagePath = $attributes['storage_path'] ?? null;

        if (is_string($storageDisk) && is_string($storagePath) && $storagePath !== '') {
            return Document::query()
                ->where('storage_disk', $storageDisk)
                ->where('storage_path', $storagePath)
                ->first();
        }

        return Document::query()
            ->when($source, fn ($query) => $query->where('source_id', $source->id), fn ($query) => $query->whereNull('source_id'))
            ->where('checksum', $checksum)
            ->first();
    }

    /**
     * @param  mixed  $current
     * @param  mixed  $incoming
     * @return array<string, mixed>
     */
    protected function mergeMetadata(mixed $current, mixed $incoming): array
    {
        $current = is_array($current) ? $current : [];
        $incoming = is_array($incoming) ? $incoming : [];

        return array_replace_recursive($current, $incoming);
    }

    protected function shouldQueueVectorSync(Document $document): bool
    {
        return $document->exists
            && $document->chunks()->whereNull('vector_id')->exists()
            && filled(config('openai.api_key'))
            && filled(config('support-assistant.models.embeddings'))
            && filled(config('vector-store.stores.'.config('vector-store.default').'.url'));
    }

    protected function shouldQueueVectorPrune(): bool
    {
        return filled(config('vector-store.stores.'.config('vector-store.default').'.url'));
    }
}
