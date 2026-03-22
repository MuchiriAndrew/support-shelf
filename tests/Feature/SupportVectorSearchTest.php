<?php

namespace Tests\Feature;

use App\Contracts\VectorStore;
use App\Models\Document;
use App\Models\Source;
use App\Services\Retrieval\SupportVectorIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Embeddings\CreateResponse;
use Tests\TestCase;

class SupportVectorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_sync_document_chunks_into_the_vector_store(): void
    {
        config()->set('openai.api_key', 'test-key');
        config()->set('support-assistant.models.embeddings', 'text-embedding-3-small');
        config()->set('vector-store.stores.weaviate.url', 'http://weaviate.test');

        $fakeStore = new class implements VectorStore
        {
            /** @var array<int, array<string, mixed>> */
            public array $upsertedRecords = [];

            public function isConfigured(): bool
            {
                return true;
            }

            public function ensureCollection(): void
            {
            }

            public function upsertChunkVectors(array $records): void
            {
                $this->upsertedRecords = array_merge($this->upsertedRecords, $records);
            }

            public function deleteVectors(array $vectorIds): void
            {
            }

            public function search(array $vector, int $limit = 8): array
            {
                return [];
            }
        };

        $this->app->instance(VectorStore::class, $fakeStore);

        OpenAI::fake([
            CreateResponse::fake([
                'model' => 'text-embedding-3-small',
                'data' => [
                    [
                        'object' => 'embedding',
                        'index' => 0,
                        'embedding' => [0.1, 0.2, 0.3],
                    ],
                    [
                        'object' => 'embedding',
                        'index' => 1,
                        'embedding' => [0.4, 0.5, 0.6],
                    ],
                ],
            ]),
        ]);

        $source = Source::query()->create([
            'name' => 'Apple AirPods Support',
            'source_type' => 'support_site',
            'domain' => 'support.apple.com',
            'url' => 'https://support.apple.com/airpods',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $document = Document::query()->create([
            'source_id' => $source->id,
            'title' => 'AirPods Reset Guide',
            'document_type' => 'support_page',
            'language' => 'en',
            'canonical_url' => 'https://support.apple.com/en-us/118531',
            'checksum' => sha1('guide'),
            'content_text' => 'Reset your AirPods by holding the setup button.',
            'token_estimate' => 32,
            'status' => 'ready',
        ]);

        $document->chunks()->create([
            'chunk_index' => 0,
            'content' => 'Reset your AirPods by placing them in the case and holding the setup button.',
            'token_estimate' => 24,
            'metadata' => [],
        ]);

        $document->chunks()->create([
            'chunk_index' => 1,
            'content' => 'Remove the AirPods from your Bluetooth list before pairing again.',
            'token_estimate' => 18,
            'metadata' => [],
        ]);

        $summary = app(SupportVectorIndexService::class)->syncDocument($document);

        $this->assertSame([
            'documents_indexed' => 1,
            'chunks_indexed' => 2,
        ], $summary);

        $this->assertCount(2, $fakeStore->upsertedRecords);
        $this->assertNotNull($document->chunks()->first()?->fresh()->vector_id);
        $this->assertSame('text-embedding-3-small', data_get($document->chunks()->first()?->fresh()->metadata, 'vector_store.embedding_model'));
    }

    public function test_it_can_search_the_vector_backed_support_endpoint(): void
    {
        config()->set('openai.api_key', 'test-key');
        config()->set('support-assistant.models.embeddings', 'text-embedding-3-small');
        config()->set('vector-store.stores.weaviate.url', 'http://weaviate.test');

        OpenAI::fake([
            CreateResponse::fake([
                'model' => 'text-embedding-3-small',
                'data' => [
                    [
                        'object' => 'embedding',
                        'index' => 0,
                        'embedding' => [0.7, 0.2, 0.1],
                    ],
                ],
            ]),
        ]);

        $source = Source::query()->create([
            'name' => 'Apple AirPods Support',
            'source_type' => 'support_site',
            'domain' => 'support.apple.com',
            'url' => 'https://support.apple.com/airpods',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $document = Document::query()->create([
            'source_id' => $source->id,
            'title' => 'AirPods Reset Guide',
            'document_type' => 'support_page',
            'language' => 'en',
            'canonical_url' => 'https://support.apple.com/en-us/118531',
            'checksum' => sha1('guide'),
            'content_text' => 'Reset your AirPods by holding the setup button.',
            'token_estimate' => 32,
            'status' => 'ready',
        ]);

        $chunk = $document->chunks()->create([
            'chunk_index' => 0,
            'content' => 'Reset your AirPods by placing them in the case and holding the setup button.',
            'token_estimate' => 24,
            'vector_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'metadata' => [],
        ]);

        $this->app->instance(VectorStore::class, new class($chunk, $document, $source) implements VectorStore
        {
            public function __construct(
                protected $chunk,
                protected $document,
                protected $source,
            ) {
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function ensureCollection(): void
            {
            }

            public function upsertChunkVectors(array $records): void
            {
            }

            public function deleteVectors(array $vectorIds): void
            {
            }

            public function search(array $vector, int $limit = 8): array
            {
                return [[
                    'vector_id' => $this->chunk->vector_id,
                    'chunk_id' => $this->chunk->id,
                    'document_id' => $this->document->id,
                    'source_id' => $this->source->id,
                    'document_title' => $this->document->title,
                    'document_type' => $this->document->document_type,
                    'source_name' => $this->source->name,
                    'canonical_url' => $this->document->canonical_url,
                    'content' => $this->chunk->content,
                    'distance' => 0.12,
                ]];
            }
        });

        $this->getJson('/api/support/search?q=How do I reset my AirPods?')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('matches.0.chunk_id', $chunk->id)
            ->assertJsonPath('matches.0.document.title', 'AirPods Reset Guide')
            ->assertJsonPath('matches.0.document.source', 'Apple AirPods Support');
    }
}
