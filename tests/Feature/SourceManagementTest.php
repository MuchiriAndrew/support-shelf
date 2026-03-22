<?php

namespace Tests\Feature;

use App\Models\CrawlRun;
use App\Models\Source;
use App\Services\Crawling\SupportSiteCrawler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_a_support_source_from_the_dashboard(): void
    {
        $response = $this->post(route('admin.ingestion.sources.store'), [
            'name' => 'Acme Support Center',
            'url' => 'https://example.com/support/',
            'content_selector' => 'main',
            'max_depth' => 3,
            'max_pages' => 25,
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('ingestion_success');

        $this->assertDatabaseHas('sources', [
            'name' => 'Acme Support Center',
            'url' => 'https://example.com/support',
            'domain' => 'example.com',
            'content_selector' => 'main',
            'crawl_enabled' => true,
        ]);

        $source = Source::query()->where('url', 'https://example.com/support')->firstOrFail();

        $this->assertSame(3, $source->metadata['max_depth'] ?? null);
        $this->assertSame(25, $source->metadata['max_pages'] ?? null);
    }

    public function test_the_crawl_command_can_target_a_source_by_id(): void
    {
        $source = Source::query()->create([
            'name' => 'Acme Support Center',
            'url' => 'https://example.com/support',
            'domain' => 'example.com',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $run = CrawlRun::query()->create([
            'source_id' => $source->id,
            'status' => 'completed',
            'triggered_by' => 'command',
            'started_at' => now(),
            'finished_at' => now(),
            'pages_discovered' => 4,
            'pages_processed' => 3,
            'documents_upserted' => 2,
        ]);

        $this->mock(SupportSiteCrawler::class, function (MockInterface $mock) use ($source, $run): void {
            $mock
                ->shouldReceive('crawlSource')
                ->once()
                ->withArgs(fn (Source $candidate, string $triggeredBy, mixed $callback): bool => $candidate->is($source) && $triggeredBy === 'command' && is_callable($callback))
                ->andReturn($run);
        });

        $this->artisan('support:crawl', ['source' => (string) $source->id])
            ->expectsOutput("Crawling [{$source->id}] {$source->name}...")
            ->expectsOutput('Completed: 3 pages processed, 2 documents changed.')
            ->assertSuccessful();
    }
}
