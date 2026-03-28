<?php

namespace Tests\Feature;

use App\Jobs\RunSourceCrawlJob;
use App\Livewire\Admin\IngestionWorkspace;
use App\Models\CrawlRun;
use App\Models\Source;
use App\Models\User;
use App\Services\Crawling\SiteCrawler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use Livewire\Livewire;
use Tests\TestCase;

class SourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_a_website_source_from_the_filament_workspace(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(IngestionWorkspace::class)
            ->set('siteData', [
                'name' => 'Acme Docs',
                'url' => 'https://example.com/support/',
            ])
            ->call('createSource');

        $this->assertDatabaseHas('sources', [
            'user_id' => $user->id,
            'name' => 'Acme Docs',
            'url' => 'https://example.com/support',
            'domain' => 'example.com',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $source = Source::query()->where('url', 'https://example.com/support')->firstOrFail();

        $this->assertSame(5, $source->metadata['max_depth'] ?? null);
        $this->assertSame(500, $source->metadata['max_pages'] ?? null);

        Bus::assertDispatched(RunSourceCrawlJob::class);
    }

    public function test_the_website_scope_only_returns_actual_website_sources(): void
    {
        $user = User::factory()->create();

        $websiteSource = Source::query()->create([
            'user_id' => $user->id,
            'name' => 'Acme Docs',
            'source_type' => 'website',
            'url' => 'https://example.com/docs',
            'domain' => 'example.com',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        $legacyWebsiteSource = Source::query()->create([
            'user_id' => $user->id,
            'name' => 'Legacy Support Site',
            'source_type' => 'support_site',
            'url' => 'https://legacy.example.com/help',
            'domain' => 'legacy.example.com',
            'crawl_enabled' => true,
            'status' => 'active',
        ]);

        Source::query()->create([
            'user_id' => $user->id,
            'name' => 'Uploaded Manuals',
            'source_type' => 'uploaded_collection',
            'crawl_enabled' => false,
            'status' => 'active',
        ]);

        $sourceIds = Source::query()
            ->ownedBy($user)
            ->website()
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$websiteSource->id, $legacyWebsiteSource->id], $sourceIds);
    }

    public function test_the_crawl_command_can_target_a_source_by_id(): void
    {
        $user = User::factory()->create();

        $source = Source::query()->create([
            'user_id' => $user->id,
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

        $this->mock(SiteCrawler::class, function (MockInterface $mock) use ($source, $run): void {
            $mock
                ->shouldReceive('crawlSource')
                ->once()
                ->withArgs(fn (Source $candidate, string $triggeredBy, mixed $callback): bool => $candidate->is($source) && $triggeredBy === 'command' && is_callable($callback))
                ->andReturn($run);
        });

        $this->artisan('knowledge:crawl', ['source' => (string) $source->id, '--user' => (string) $user->id])
            ->expectsOutput("Crawling [{$source->id}] {$source->name}...")
            ->expectsOutput('Completed: 3 pages processed, 2 documents changed.')
            ->assertSuccessful();
    }
}
