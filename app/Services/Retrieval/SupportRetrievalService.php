<?php

namespace App\Services\Retrieval;

use App\Contracts\VectorStore;
use App\Models\DocumentChunk;
use App\Services\Embeddings\OpenAiEmbeddingService;
use Illuminate\Support\Collection;
use RuntimeException;

class SupportRetrievalService
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
     * @return Collection<int, array{
     *     chunk_id: int,
     *     vector_id: string|null,
     *     distance: float|null,
     *     content: string,
     *     document: array{id: int|null, title: string|null, document_type: string|null, canonical_url: string|null, source: string|null}
     * }>
     */
    public function search(string $query, ?int $limit = null): Collection
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Semantic retrieval is not configured yet.');
        }

        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $limit ??= (int) config('support-assistant.retrieval.top_k', 8);
        $queryVector = $this->embeddingService->embedText($query);
        $matches = $this->vectorStore->search($queryVector, $limit);

        if ($matches === []) {
            return collect();
        }

        $chunkIds = collect($matches)
            ->pluck('chunk_id')
            ->filter(fn (mixed $id): bool => $id !== null)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $chunks = DocumentChunk::query()
            ->with('document.source')
            ->whereKey($chunkIds)
            ->get()
            ->keyBy('id');

        return collect($matches)
            ->map(function (array $match) use ($chunks): ?array {
                $chunkId = (int) ($match['chunk_id'] ?? 0);
                $chunk = $chunks->get($chunkId);

                if (! $chunk) {
                    return null;
                }

                return [
                    'chunk_id' => $chunk->id,
                    'vector_id' => $match['vector_id'] ?? $chunk->vector_id,
                    'distance' => $match['distance'],
                    'content' => $chunk->content,
                    'document' => [
                        'id' => $chunk->document?->id,
                        'title' => $chunk->document?->title,
                        'document_type' => $chunk->document?->document_type,
                        'canonical_url' => $chunk->document?->canonical_url,
                        'source' => $chunk->document?->source?->name,
                    ],
                ];
            })
            ->filter()
            ->values();
    }
}
