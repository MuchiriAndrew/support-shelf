<?php

namespace App\Services\Retrieval;

use App\Contracts\VectorStore;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Source;
use App\Services\Embeddings\OpenAiEmbeddingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;

class SupportVectorIndexService
{
    public function __construct(
        protected OpenAiEmbeddingService $embeddingService,
        protected VectorStore $vectorStore,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->embeddingService->isConfigured() && $this->vectorStore->isConfigured();
    }

    /**
     * @return array{documents_indexed: int, chunks_indexed: int}
     */
    public function syncDocument(Document|int $document, bool $force = false): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Embeddings or the vector store are not configured yet.');
        }

        $document = $document instanceof Document
            ? $document->loadMissing('source')
            : Document::query()->with('source')->findOrFail($document);

        /** @var Collection<int, DocumentChunk> $chunks */
        $chunks = $document->chunks()
            ->orderBy('chunk_index')
            ->when(! $force, fn (Builder $query): Builder => $query->whereNull('vector_id'))
            ->get();

        if ($chunks->isEmpty()) {
            return [
                'documents_indexed' => 0,
                'chunks_indexed' => 0,
            ];
        }

        $batchSize = 32;
        $indexedChunks = 0;

        foreach ($chunks->chunk($batchSize) as $batch) {
            $vectors = $this->embeddingService->embedTexts($batch->pluck('content')->all());

            if (count($vectors) !== $batch->count()) {
                throw new RuntimeException('The embedding response count did not match the number of chunks sent.');
            }

            $records = [];

            foreach ($batch->values() as $index => $chunk) {
                $vectorId = $chunk->vector_id ?: $this->deterministicVectorId($chunk);

                $records[] = [
                    'id' => $vectorId,
                    'chunk_id' => $chunk->id,
                    'document_id' => $document->id,
                    'source_id' => $document->source_id,
                    'source_name' => $document->source?->name,
                    'document_title' => $document->title,
                    'document_type' => $document->document_type,
                    'canonical_url' => $document->canonical_url,
                    'content' => $chunk->content,
                    'vector' => $vectors[$index],
                ];
            }

            $this->vectorStore->upsertChunkVectors($records);

            foreach ($batch->values() as $index => $chunk) {
                $metadata = is_array($chunk->metadata) ? $chunk->metadata : [];

                $chunk->forceFill([
                    'vector_id' => $records[$index]['id'],
                    'metadata' => array_replace_recursive($metadata, [
                        'vector_store' => [
                            'driver' => config('vector-store.default'),
                            'embedding_model' => $this->embeddingService->model(),
                            'indexed_at' => now()->toIso8601String(),
                        ],
                    ]),
                ])->save();

                $indexedChunks++;
            }
        }

        return [
            'documents_indexed' => 1,
            'chunks_indexed' => $indexedChunks,
        ];
    }

    /**
     * @return array{documents_indexed: int, chunks_indexed: int}
     */
    public function syncPendingDocuments(?Source $source = null, int $limit = 50, bool $force = false): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Embeddings or the vector store are not configured yet.');
        }

        $documents = Document::query()
            ->with('source')
            ->when($source, fn (Builder $query): Builder => $query->where('source_id', $source->id))
            ->when(
                ! $force,
                fn (Builder $query): Builder => $query->whereHas('chunks', fn (Builder $chunkQuery): Builder => $chunkQuery->whereNull('vector_id'))
            )
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $summary = [
            'documents_indexed' => 0,
            'chunks_indexed' => 0,
        ];

        foreach ($documents as $document) {
            $result = $this->syncDocument($document, $force);
            $summary['documents_indexed'] += $result['documents_indexed'];
            $summary['chunks_indexed'] += $result['chunks_indexed'];
        }

        return $summary;
    }

    protected function deterministicVectorId(DocumentChunk $chunk): string
    {
        $hash = md5("support-chunk:{$chunk->getKey()}");

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }
}
