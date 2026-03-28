<?php

namespace App\Contracts;

interface VectorStore
{
    public function isConfigured(): bool;

    public function ensureCollection(): void;

    /**
     * @param  array<int, array{
     *     id: string,
     *     chunk_id: int,
     *     document_id: int,
     *     user_id: int,
     *     source_id: int|null,
     *     source_name: string|null,
     *     document_title: string,
     *     document_type: string,
     *     canonical_url: string|null,
     *     content: string,
     *     vector: array<int, float>
     * }>  $records
     */
    public function upsertChunkVectors(array $records): void;

    /**
     * @param  list<string>  $vectorIds
     */
    public function deleteVectors(array $vectorIds): void;

    /**
     * @param  array<int, float>  $vector
     * @param  array<string, mixed>  $filters
     * @return array<int, array{
     *     vector_id: string|null,
     *     chunk_id: int|null,
     *     document_id: int|null,
     *     user_id: int|null,
     *     source_id: int|null,
     *     document_title: string|null,
     *     document_type: string|null,
     *     source_name: string|null,
     *     canonical_url: string|null,
     *     content: string|null,
     *     distance: float|null
     * }>
     */
    public function search(array $vector, int $limit = 8, array $filters = []): array;
}
