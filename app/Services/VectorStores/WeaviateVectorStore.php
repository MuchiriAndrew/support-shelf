<?php

namespace App\Services\VectorStores;

use App\Contracts\VectorStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WeaviateVectorStore implements VectorStore
{
    protected bool $collectionEnsured = false;

    public function isConfigured(): bool
    {
        return filled(config('vector-store.stores.weaviate.url'));
    }

    public function ensureCollection(): void
    {
        if ($this->collectionEnsured) {
            return;
        }

        $response = $this->request()->get('/v1/schema');

        $this->throwIfFailed($response);

        $classes = collect($response->json('classes', []))
            ->pluck('class')
            ->filter(fn (mixed $class): bool => is_string($class))
            ->all();

        if (! in_array($this->className(), $classes, true)) {
            $createResponse = $this->request()->post('/v1/schema', [
                'class' => $this->className(),
                'description' => 'Support document chunks indexed with external OpenAI embeddings.',
                'vectorizer' => 'none',
                'properties' => [
                    ['name' => 'chunkId', 'dataType' => ['int']],
                    ['name' => 'documentId', 'dataType' => ['int']],
                    ['name' => 'sourceId', 'dataType' => ['int']],
                    ['name' => 'sourceName', 'dataType' => ['text']],
                    ['name' => 'documentTitle', 'dataType' => ['text']],
                    ['name' => 'documentType', 'dataType' => ['text']],
                    ['name' => 'canonicalUrl', 'dataType' => ['text']],
                    ['name' => 'content', 'dataType' => ['text']],
                ],
            ]);

            $this->throwIfFailed($createResponse);
        }

        $this->collectionEnsured = true;
    }

    public function upsertChunkVectors(array $records): void
    {
        if ($records === []) {
            return;
        }

        $this->ensureCollection();

        $response = $this->request()->post('/v1/batch/objects', [
            'objects' => array_map(function (array $record): array {
                return [
                    'class' => $this->className(),
                    'id' => $record['id'],
                    'properties' => [
                        'chunkId' => $record['chunk_id'],
                        'documentId' => $record['document_id'],
                        'sourceId' => $record['source_id'],
                        'sourceName' => $record['source_name'],
                        'documentTitle' => $record['document_title'],
                        'documentType' => $record['document_type'],
                        'canonicalUrl' => $record['canonical_url'],
                        'content' => $record['content'],
                    ],
                    'vector' => $record['vector'],
                ];
            }, $records),
        ]);

        $this->throwIfFailed($response);

        $results = $response->json();

        if (! is_array($results)) {
            throw new RuntimeException('Weaviate returned an unexpected batch response.');
        }

        foreach ($results as $result) {
            $status = data_get($result, 'result.status');

            if ($status !== 'SUCCESS') {
                throw new RuntimeException('Weaviate failed to persist one or more chunk vectors.');
            }
        }
    }

    public function deleteVectors(array $vectorIds): void
    {
        if ($vectorIds === []) {
            return;
        }

        $this->ensureCollection();

        foreach (array_values(array_unique($vectorIds)) as $vectorId) {
            $response = $this->request()->delete("/v1/objects/{$vectorId}");

            if ($response->status() === 404) {
                continue;
            }

            $this->throwIfFailed($response);
        }
    }

    public function search(array $vector, int $limit = 8): array
    {
        if ($vector === []) {
            return [];
        }

        $this->ensureCollection();

        $vectorJson = json_encode(array_values($vector), JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        $limit = max(1, $limit);
        $className = $this->className();

        $query = <<<GRAPHQL
        {
          Get {
            {$className}(nearVector: {vector: {$vectorJson}}, limit: {$limit}) {
              chunkId
              documentId
              sourceId
              sourceName
              documentTitle
              documentType
              canonicalUrl
              content
              _additional {
                id
                distance
              }
            }
          }
        }
        GRAPHQL;

        $response = $this->request()->post('/v1/graphql', [
            'query' => $query,
        ]);

        $this->throwIfFailed($response);

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Weaviate returned an unexpected search response.');
        }

        if (isset($payload['errors'])) {
            $message = data_get($payload, 'errors.0.message', 'Weaviate search failed.');

            throw new RuntimeException((string) $message);
        }

        $results = data_get($payload, "data.Get.{$className}", []);

        if (! is_array($results)) {
            return [];
        }

        return array_values(array_map(static function (mixed $result): array {
            return [
                'vector_id' => data_get($result, '_additional.id'),
                'chunk_id' => ($chunkId = data_get($result, 'chunkId')) !== null ? (int) $chunkId : null,
                'document_id' => ($documentId = data_get($result, 'documentId')) !== null ? (int) $documentId : null,
                'source_id' => ($sourceId = data_get($result, 'sourceId')) !== null ? (int) $sourceId : null,
                'document_title' => data_get($result, 'documentTitle'),
                'document_type' => data_get($result, 'documentType'),
                'source_name' => data_get($result, 'sourceName'),
                'canonical_url' => data_get($result, 'canonicalUrl'),
                'content' => data_get($result, 'content'),
                'distance' => ($distance = data_get($result, '_additional.distance')) !== null ? (float) $distance : null,
            ];
        }, $results));
    }

    protected function className(): string
    {
        $collection = (string) config('vector-store.stores.weaviate.collection', 'support_chunks');

        return Str::studly(str_replace(['-', '.'], '_', $collection));
    }

    protected function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Weaviate is not configured. Set WEAVIATE_URL before syncing vectors.');
        }

        $request = Http::baseUrl((string) config('vector-store.stores.weaviate.url'))
            ->acceptJson()
            ->asJson()
            ->timeout((float) config('vector-store.stores.weaviate.timeout', 10));

        if (filled($apiKey = config('vector-store.stores.weaviate.api_key'))) {
            $request = $request->withToken((string) $apiKey);
        }

        return $request;
    }

    protected function throwIfFailed($response): void
    {
        try {
            $response->throw();
        } catch (RequestException $exception) {
            $message = $response->json('error.0.message')
                ?? $response->json('error.message')
                ?? $exception->getMessage();

            throw new RuntimeException((string) $message, previous: $exception);
        }
    }
}
