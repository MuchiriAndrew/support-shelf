<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Models\User;
use App\Services\Crawling\SiteCrawler;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class CrawlKnowledgeSourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:crawl
                            {source? : A source id, name, or URL}
                            {--all : Crawl all active sources}
                            {--user= : Optional user id or email to scope the source lookup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl one or more registered website sources';

    /**
     * Execute the console command.
     */
    public function handle(SiteCrawler $crawler): int
    {
        $sources = $this->resolveSources();

        if ($sources->isEmpty()) {
            $this->warn('No matching crawlable sources were found.');

            return self::FAILURE;
        }

        foreach ($sources as $source) {
            $this->line("Crawling [{$source->id}] {$source->name}...");

            try {
                $lastReportedVisitCount = 0;

                $run = $crawler->crawlSource($source, 'command', function (array $progress) use (&$lastReportedVisitCount): void {
                    $visitedPages = (int) ($progress['pages_visited'] ?? 0);

                    if ($visitedPages === 0 || $visitedPages === $lastReportedVisitCount) {
                        return;
                    }

                    $lastReportedVisitCount = $visitedPages;

                    $this->line(sprintf(
                        'Progress: %d visited, %d processed, %d documents changed.',
                        $visitedPages,
                        (int) ($progress['pages_processed'] ?? 0),
                        (int) ($progress['documents_upserted'] ?? 0),
                    ));
                });
            } catch (Throwable $exception) {
                $this->error("Failed: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $this->info(sprintf(
                'Completed: %d pages processed, %d documents changed.',
                $run->pages_processed,
                $run->documents_upserted,
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Source>
     */
    protected function resolveSources()
    {
        $sourceArgument = $this->argument('source');
        $user = $this->resolveUser();

        if ($this->option('all') || $sourceArgument === null) {
            return Source::query()
                ->when($user, fn ($query) => $query->ownedBy($user))
                ->crawlable()
                ->get();
        }

        return Source::query()
            ->when($user, fn ($query) => $query->ownedBy($user))
            ->crawlable()
            ->where(function ($query) use ($sourceArgument): void {
                if (is_numeric($sourceArgument)) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), (int) $sourceArgument);
                }

                $query
                    ->orWhere('name', $sourceArgument)
                    ->orWhere('url', $sourceArgument);
            })
            ->get();
    }

    protected function resolveUser(): ?User
    {
        $value = trim((string) $this->option('user'));

        if ($value === '') {
            return null;
        }

        return User::query()
            ->when(is_numeric($value), fn ($query) => $query->whereKey((int) $value), fn ($query) => $query->where('email', $value))
            ->firstOr(fn () => throw new RuntimeException("No user found for [{$value}]."));
    }
}
