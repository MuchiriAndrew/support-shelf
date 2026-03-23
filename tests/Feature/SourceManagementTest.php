<?php

namespace Tests\Feature;

use App\Jobs\RunSourceCrawlJob;
use App\Livewire\Admin\IngestionWorkspace;
use App\Models\CrawlRun;
use App\Models\Source;
use App\Models\User;
use App\Services\Crawling\SupportSiteCrawler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use Livewire\Livewire;
use Tests\TestCase;

class SourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_a_support_source_from_the_filament_workspace(): void
    {
        Bus::fake();

        $this->actingAs(User::factory()->create());

        Livewire::test(IngestionWorkspace::class)
            ->set('siteData', [
                'name' => 'Acme Support Center',
                'url' => 'https://example.com/support/',
                'content_selector' => 'main',
                'status' => 'active',
                'crawl_enabled' => true,
                'crawl_now' => true,
                'max_depth' => 3,
                'max_pages' => 25,
            ])
            ->call('createSource');

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

        Bus::assertDispatched(RunSourceCrawlJob::class);
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
