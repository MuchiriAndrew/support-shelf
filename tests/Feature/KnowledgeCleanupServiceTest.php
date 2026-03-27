<?php

namespace Tests\Feature;

use App\Jobs\DeleteChunkVectorsJob;
use App\Models\CrawlRun;
use App\Models\Document;
use App\Models\Source;
use App\Services\Ingestion\KnowledgeCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KnowledgeCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_delete_a_document_and_queue_vector_cleanup(): void
    {
        Bus::fake();
        Storage::fake('local');

        $source = Source::query()->create([
            'name' => 'Store policies',
            'source_type' => 'uploaded_documents',
        ]);

        $document = Document::query()->create([
            'source_id' => $source->id,
            'title' => 'Returns policy',
            'document_type' => 'return_policy',
            'storage_disk' => 'local',
            'storage_path' => 'source-documents/returns-policy.txt',
            'content_text' => 'Returns content',
            'checksum' => sha1('Returns content'),
        ]);

        $document->chunks()->createMany([
            [
                'chunk_index' => 0,
                'content' => 'Returns chunk one',
                'token_estimate' => 16,
                'vector_id' => 'vec-1',
            ],
            [
                'chunk_index' => 1,
                'content' => 'Returns chunk two',
                'token_estimate' => 12,
                'vector_id' => 'vec-2',
            ],
        ]);

        Storage::disk('local')->put($document->storage_path, 'Returns content');

        $result = app(KnowledgeCleanupService::class)->deleteDocument($document);

        $this->assertSame(2, $result['chunks_deleted']);
        $this->assertSame(2, $result['vector_ids_deleted']);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        $this->assertDatabaseCount('document_chunks', 0);
        $this->assertFalse(Storage::disk('local')->exists('source-documents/returns-policy.txt'));

        Bus::assertDispatched(DeleteChunkVectorsJob::class, fn (DeleteChunkVectorsJob $job): bool => $job->vectorIds === ['vec-1', 'vec-2']);
    }

    public function test_it_can_delete_a_source_and_all_of_its_context(): void
    {
        Bus::fake();
        Storage::fake('local');

        $source = Source::query()->create([
            'name' => 'Apple AirPods Support',
            'source_type' => 'support_site',
            'url' => 'https://example.com/support',
            'domain' => 'example.com',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        CrawlRun::query()->create([
            'source_id' => $source->id,
            'status' => 'completed',
            'triggered_by' => 'filament',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $manual = Document::query()->create([
            'source_id' => $source->id,
            'title' => 'Manual',
            'document_type' => 'manual_pdf',
            'storage_disk' => 'local',
            'storage_path' => 'source-documents/manual.pdf',
            'content_text' => 'Manual content',
            'checksum' => sha1('Manual content'),
        ]);

        $guide = Document::query()->create([
            'source_id' => $source->id,
            'title' => 'Guide',
            'document_type' => 'support_page',
            'content_text' => 'Guide content',
            'checksum' => sha1('Guide content'),
        ]);

        $manual->chunks()->create([
            'chunk_index' => 0,
            'content' => 'Manual chunk',
            'token_estimate' => 20,
            'vector_id' => 'vec-a',
        ]);

        $guide->chunks()->create([
            'chunk_index' => 0,
            'content' => 'Guide chunk',
            'token_estimate' => 24,
            'vector_id' => 'vec-b',
        ]);

        Storage::disk('local')->put('source-documents/manual.pdf', 'Manual content');

        $result = app(KnowledgeCleanupService::class)->deleteSource($source);

        $this->assertSame(2, $result['documents_deleted']);
        $this->assertSame(2, $result['chunks_deleted']);
        $this->assertSame(2, $result['vector_ids_deleted']);
        $this->assertDatabaseMissing('sources', ['id' => $source->id]);
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_chunks', 0);
        $this->assertDatabaseCount('crawl_runs', 0);
        $this->assertFalse(Storage::disk('local')->exists('source-documents/manual.pdf'));

        Bus::assertDispatched(DeleteChunkVectorsJob::class, fn (DeleteChunkVectorsJob $job): bool => $job->vectorIds === ['vec-a', 'vec-b']);
    }
}
