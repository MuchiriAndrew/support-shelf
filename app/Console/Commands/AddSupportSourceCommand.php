<?php

namespace App\Console\Commands;

use App\Services\Ingestion\SourceRegistryService;
use Illuminate\Console\Command;

class AddSupportSourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:source:add
                            {name : Human-readable name for the source}
                            {url : The starting URL for the crawl}
                            {--selector= : Optional CSS selector for the main content area}
                            {--type=support_site : Logical source type}
                            {--max-depth= : Override the default crawl depth}
                            {--max-pages= : Override the default crawl page limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register or update a support source for crawling';

    /**
     * Execute the console command.
     */
    public function handle(SourceRegistryService $registry): int
    {
        $metadata = [];

        if (($maxDepth = $this->option('max-depth')) !== null) {
            $metadata['max_depth'] = (int) $maxDepth;
        }

        if (($maxPages = $this->option('max-pages')) !== null) {
            $metadata['max_pages'] = max(1, (int) $maxPages);
        }

        $source = $registry->registerWebsiteSource([
            'name' => (string) $this->argument('name'),
            'url' => (string) $this->argument('url'),
            'source_type' => (string) $this->option('type'),
            'content_selector' => $this->option('selector') ?: null,
            'metadata' => $metadata,
        ]);

        $this->info("Registered source [{$source->id}] {$source->name}");

        return self::SUCCESS;
    }
}
