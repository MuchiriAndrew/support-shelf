<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Ingestion\SourceRegistryService;
use Illuminate\Console\Command;
use RuntimeException;

class AddKnowledgeSourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:source:add
                            {name : Human-readable name for the source}
                            {url : The starting URL for the crawl}
                            {--selector= : Optional CSS selector for the main content area}
                            {--type=website : Logical source type}
                            {--user= : Required user id or email that will own the source}
                            {--max-depth= : Override the default crawl depth}
                            {--max-pages= : Override the default crawl page limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register or update a crawlable website source for a user workspace';

    /**
     * Execute the console command.
     */
    public function handle(SourceRegistryService $registry): int
    {
        try {
            $user = $this->resolveUser();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $metadata = [];

        if (($maxDepth = $this->option('max-depth')) !== null) {
            $metadata['max_depth'] = (int) $maxDepth;
        }

        if (($maxPages = $this->option('max-pages')) !== null) {
            $metadata['max_pages'] = max(1, (int) $maxPages);
        }

        $source = $registry->registerWebsiteSource($user, [
            'name' => (string) $this->argument('name'),
            'url' => (string) $this->argument('url'),
            'source_type' => (string) $this->option('type'),
            'content_selector' => $this->option('selector') ?: null,
            'metadata' => $metadata,
        ]);

        $this->info("Registered source [{$source->id}] {$source->name}");

        return self::SUCCESS;
    }

    protected function resolveUser(): User
    {
        $value = trim((string) $this->option('user'));

        if ($value === '') {
            throw new RuntimeException('Provide --user=<id-or-email> so the source is assigned to a workspace.');
        }

        return User::query()
            ->when(is_numeric($value), fn ($query) => $query->whereKey((int) $value), fn ($query) => $query->where('email', $value))
            ->firstOr(fn () => throw new RuntimeException("No user found for [{$value}]."));
    }
}
