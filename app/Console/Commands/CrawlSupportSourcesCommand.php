<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Services\Crawling\SupportSiteCrawler;
use Illuminate\Console\Command;
use Throwable;

class CrawlSupportSourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:crawl
                            {source? : A source id, name, or URL}
                            {--all : Crawl all active sources}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl one or more registered support sources';

    /**
     * Execute the console command.
     */
    public function handle(SupportSiteCrawler $crawler): int
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

        if ($this->option('all') || $sourceArgument === null) {
            return Source::query()->crawlable()->get();
        }

        return Source::query()
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
}
