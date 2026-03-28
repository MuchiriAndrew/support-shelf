<?php

namespace App\Services\Ingestion;

use App\Jobs\DeleteChunkVectorsJob;
use App\Models\Document;
use App\Models\Source;
use App\Support\ActivityLog;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KnowledgeCleanupService
{
    /**
     * @return array{chunks_deleted: int, vector_ids_deleted: int}
     */
    public function deleteDocument(Document $document): array
    {
        $document->loadMissing('chunks');

        $storageDisk = $document->storage_disk;
        $storagePath = $document->storage_path;
        $vectorIds = $document->chunks
            ->pluck('vector_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $chunksDeleted = $document->chunks->count();

        ActivityLog::info('Knowledge document deletion started', [
            'document_id' => $document->id,
            'document_title' => $document->title,
            'chunks_count' => $chunksDeleted,
            'vector_ids_count' => count($vectorIds),
            'storage_disk' => $storageDisk,
            'storage_path' => $storagePath,
        ]);

        DB::transaction(function () use ($document): void {
            $document->delete();
        });

        $this->deleteStoredFile($storageDisk, $storagePath);
        $this->queueVectorDeletion($vectorIds);

        ActivityLog::info('Knowledge document deletion completed', [
            'document_id' => $document->id,
            'document_title' => $document->title,
            'chunks_deleted' => $chunksDeleted,
            'vector_ids_deleted' => count($vectorIds),
        ]);

        return [
            'chunks_deleted' => $chunksDeleted,
            'vector_ids_deleted' => count($vectorIds),
        ];
    }

    /**
     * @param  EloquentCollection<int, Document>|iterable<Document>  $documents
     * @return array{documents_deleted: int, chunks_deleted: int, vector_ids_deleted: int}
     */
    public function deleteDocuments(iterable $documents): array
    {
        $documents = $documents instanceof EloquentCollection ? $documents : collect($documents);

        $documentsDeleted = 0;
        $chunksDeleted = 0;
        $vectorIdsDeleted = 0;

        foreach ($documents as $document) {
            $result = $this->deleteDocument($document);
            $documentsDeleted++;
            $chunksDeleted += $result['chunks_deleted'];
            $vectorIdsDeleted += $result['vector_ids_deleted'];
        }

        return [
            'documents_deleted' => $documentsDeleted,
            'chunks_deleted' => $chunksDeleted,
            'vector_ids_deleted' => $vectorIdsDeleted,
        ];
    }

    /**
     * @return array{documents_deleted: int, chunks_deleted: int, vector_ids_deleted: int}
     */
    public function deleteSource(Source $source): array
    {
        $source->loadMissing(['documents.chunks', 'crawlRuns']);

        $documents = $source->documents;
        $vectorIds = $documents
            ->flatMap(fn (Document $document) => $document->chunks->pluck('vector_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $storedFiles = $documents
            ->map(fn (Document $document): ?array => filled($document->storage_disk) && filled($document->storage_path)
                ? ['disk' => $document->storage_disk, 'path' => $document->storage_path]
                : null)
            ->filter()
            ->unique(fn (array $file): string => "{$file['disk']}::{$file['path']}")
            ->values()
            ->all();
        $documentsDeleted = $documents->count();
        $chunksDeleted = $documents->sum(fn (Document $document): int => $document->chunks->count());

        ActivityLog::info('Knowledge source deletion started', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'documents_count' => $documentsDeleted,
            'chunks_count' => $chunksDeleted,
            'vector_ids_count' => count($vectorIds),
            'crawl_runs_count' => $source->crawlRuns->count(),
        ]);

        DB::transaction(function () use ($source, $documents): void {
            $source->crawlRuns()->delete();

            $documents->each(fn (Document $document) => $document->delete());

            $source->delete();
        });

        foreach ($storedFiles as $file) {
            $this->deleteStoredFile($file['disk'], $file['path']);
        }

        $this->queueVectorDeletion($vectorIds);

        ActivityLog::info('Knowledge source deletion completed', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'documents_deleted' => $documentsDeleted,
            'chunks_deleted' => $chunksDeleted,
            'vector_ids_deleted' => count($vectorIds),
        ]);

        return [
            'documents_deleted' => $documentsDeleted,
            'chunks_deleted' => $chunksDeleted,
            'vector_ids_deleted' => count($vectorIds),
        ];
    }

    /**
     * @param  EloquentCollection<int, Source>|iterable<Source>  $sources
     * @return array{sources_deleted: int, documents_deleted: int, chunks_deleted: int, vector_ids_deleted: int}
     */
    public function deleteSources(iterable $sources): array
    {
        $sources = $sources instanceof EloquentCollection ? $sources : collect($sources);

        $sourcesDeleted = 0;
        $documentsDeleted = 0;
        $chunksDeleted = 0;
        $vectorIdsDeleted = 0;

        foreach ($sources as $source) {
            $result = $this->deleteSource($source);
            $sourcesDeleted++;
            $documentsDeleted += $result['documents_deleted'];
            $chunksDeleted += $result['chunks_deleted'];
            $vectorIdsDeleted += $result['vector_ids_deleted'];
        }

        return [
            'sources_deleted' => $sourcesDeleted,
            'documents_deleted' => $documentsDeleted,
            'chunks_deleted' => $chunksDeleted,
            'vector_ids_deleted' => $vectorIdsDeleted,
        ];
    }

    protected function deleteStoredFile(?string $disk, ?string $path): void
    {
        if (blank($disk) || blank($path)) {
            return;
        }

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    /**
     * @param  list<string>  $vectorIds
     */
    protected function queueVectorDeletion(array $vectorIds): void
    {
        if ($vectorIds === []) {
            return;
        }

        DeleteChunkVectorsJob::dispatch($vectorIds);
    }
}
