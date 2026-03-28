<?php

namespace Tests\Feature;

use App\Contracts\VectorStore;
use App\Models\Document;
use App\Models\Source;
use App\Models\User;
use App\Services\Retrieval\KnowledgeVectorIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Embeddings\CreateResponse;
use Tests\TestCase;

class KnowledgeVectorSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_sync_document_chunks_into_the_vector_store(): void
    {
        config()->set('openai.api_key', 'test-key');
        config()->set('assistant.models.embeddings', 'text-embedding-3-small');
        config()->set('vector-store.stores.weaviate.url', 'http://weaviate.test');
        $user = User::factory()->create();

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

            public function search(array $vector, int $limit = 8, array $filters = []): array
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
            'user_id' => $user->id,
            'name' => 'Apple AirPods Support',
            'source_type' => 'website',
            'domain' => 'support.apple.com',
            'url' => 'https://support.apple.com/airpods',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $document = Document::query()->create([
            'user_id' => $user->id,
            'source_id' => $source->id,
            'title' => 'AirPods Reset Guide',
            'document_type' => 'web_page',
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

        $summary = app(KnowledgeVectorIndexService::class)->syncDocument($document);

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
        config()->set('assistant.models.embeddings', 'text-embedding-3-small');
        config()->set('vector-store.stores.weaviate.url', 'http://weaviate.test');
        $user = User::factory()->create();

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
            'user_id' => $user->id,
            'name' => 'Apple AirPods Support',
            'source_type' => 'website',
            'domain' => 'support.apple.com',
            'url' => 'https://support.apple.com/airpods',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $document = Document::query()->create([
            'user_id' => $user->id,
            'source_id' => $source->id,
            'title' => 'AirPods Reset Guide',
            'document_type' => 'web_page',
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

            public function search(array $vector, int $limit = 8, array $filters = []): array
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

        $this->actingAs($user)
            ->getJson('/api/knowledge/search?q=How do I reset my AirPods?')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('matches.0.chunk_id', $chunk->id)
            ->assertJsonPath('matches.0.document.title', 'AirPods Reset Guide')
            ->assertJsonPath('matches.0.document.source', 'Apple AirPods Support');
    }

    public function test_vector_search_does_not_leak_another_users_documents(): void
    {
        config()->set('openai.api_key', 'test-key');
        config()->set('assistant.models.embeddings', 'text-embedding-3-small');
        config()->set('vector-store.stores.weaviate.url', 'http://weaviate.test');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        OpenAI::fake([
            CreateResponse::fake([
                'model' => 'text-embedding-3-small',
                'data' => [
                    [
                        'object' => 'embedding',
                        'index' => 0,
                        'embedding' => [0.3, 0.2, 0.8],
                    ],
                ],
            ]),
        ]);

        $ownerSource = Source::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Docs',
            'source_type' => 'website',
            'domain' => 'owner.example.com',
            'url' => 'https://owner.example.com/docs',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $otherSource = Source::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Docs',
            'source_type' => 'website',
            'domain' => 'other.example.com',
            'url' => 'https://other.example.com/docs',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $ownerDocument = Document::query()->create([
            'user_id' => $owner->id,
            'source_id' => $ownerSource->id,
            'title' => 'Owner Guide',
            'document_type' => 'web_page',
            'language' => 'en',
            'canonical_url' => 'https://owner.example.com/docs/guide',
            'checksum' => sha1('owner-guide'),
            'content_text' => 'Owner private guide.',
            'token_estimate' => 12,
            'status' => 'ready',
        ]);

        $otherDocument = Document::query()->create([
            'user_id' => $otherUser->id,
            'source_id' => $otherSource->id,
            'title' => 'Other Guide',
            'document_type' => 'web_page',
            'language' => 'en',
            'canonical_url' => 'https://other.example.com/docs/guide',
            'checksum' => sha1('other-guide'),
            'content_text' => 'Other private guide.',
            'token_estimate' => 12,
            'status' => 'ready',
        ]);

        $ownerChunk = $ownerDocument->chunks()->create([
            'chunk_index' => 0,
            'content' => 'Owner-only retrieval content.',
            'token_estimate' => 10,
            'vector_id' => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'metadata' => [],
        ]);

        $otherChunk = $otherDocument->chunks()->create([
            'chunk_index' => 0,
            'content' => 'Other-user retrieval content.',
            'token_estimate' => 10,
            'vector_id' => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'metadata' => [],
        ]);

        $this->app->instance(VectorStore::class, new class($ownerChunk, $ownerDocument, $ownerSource, $otherChunk, $otherDocument, $otherSource) implements VectorStore
        {
            public function __construct(
                protected $ownerChunk,
                protected $ownerDocument,
                protected $ownerSource,
                protected $otherChunk,
                protected $otherDocument,
                protected $otherSource,
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

            public function search(array $vector, int $limit = 8, array $filters = []): array
            {
                return [
                    [
                        'vector_id' => $this->ownerChunk->vector_id,
                        'chunk_id' => $this->ownerChunk->id,
                        'document_id' => $this->ownerDocument->id,
                        'source_id' => $this->ownerSource->id,
                        'document_title' => $this->ownerDocument->title,
                        'document_type' => $this->ownerDocument->document_type,
                        'source_name' => $this->ownerSource->name,
                        'canonical_url' => $this->ownerDocument->canonical_url,
                        'content' => $this->ownerChunk->content,
                        'distance' => 0.08,
                        'user_id' => $filters['user_id'] ?? null,
                    ],
                    [
                        'vector_id' => $this->otherChunk->vector_id,
                        'chunk_id' => $this->otherChunk->id,
                        'document_id' => $this->otherDocument->id,
                        'source_id' => $this->otherSource->id,
                        'document_title' => $this->otherDocument->title,
                        'document_type' => $this->otherDocument->document_type,
                        'source_name' => $this->otherSource->name,
                        'canonical_url' => $this->otherDocument->canonical_url,
                        'content' => $this->otherChunk->content,
                        'distance' => 0.06,
                        'user_id' => 999999,
                    ],
                ];
            }
        });

        $this->actingAs($owner)
            ->getJson('/api/knowledge/search?q=private guide')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('matches.0.chunk_id', $ownerChunk->id)
            ->assertJsonMissing(['chunk_id' => $otherChunk->id]);
    }
}
