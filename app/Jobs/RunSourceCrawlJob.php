<?php

namespace App\Jobs;

use App\Models\Source;
use App\Services\Crawling\SiteCrawler;
use App\Support\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class RunSourceCrawlJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $sourceId,
        public string $triggeredBy = 'job',
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SiteCrawler $crawler): void
    {
        ActivityLog::info('RunSourceCrawlJob started', [
            'source_id' => $this->sourceId,
            'triggered_by' => $this->triggeredBy,
            'queue_connection' => config('queue.default'),
        ]);

        $source = Source::query()->find($this->sourceId);

        if (! $source) {
            ActivityLog::error('RunSourceCrawlJob failed because the source record could not be found', [
                'source_id' => $this->sourceId,
                'triggered_by' => $this->triggeredBy,
            ]);

            throw new RuntimeException("Support crawl source [{$this->sourceId}] could not be found.");
        }

        $metadata = is_array($source->metadata) ? $source->metadata : [];

        ActivityLog::debug('RunSourceCrawlJob resolved source', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'source_url' => $source->url,
            'source_domain' => $source->domain,
            'crawl_enabled' => $source->crawl_enabled,
            'source_status' => $source->status,
            'content_selector' => $source->content_selector,
            'max_depth' => $metadata['max_depth'] ?? config('crawling.max_depth'),
            'max_pages' => $metadata['max_pages'] ?? config('crawling.max_pages'),
            'triggered_by' => $this->triggeredBy,
        ]);

        $startedAt = microtime(true);

        try {
            $run = $crawler->crawlSource($source, $this->triggeredBy);
        } catch (Throwable $exception) {
            ActivityLog::error('RunSourceCrawlJob failed during crawl execution', [
                'source_id' => $source->id,
                'source_name' => $source->name,
                'source_url' => $source->url,
                'triggered_by' => $this->triggeredBy,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'exception' => $exception,
            ]);

            throw $exception;
        }

        ActivityLog::info('RunSourceCrawlJob completed', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'triggered_by' => $this->triggeredBy,
            'crawl_run_id' => $run->id,
            'crawl_status' => $run->status,
            'pages_discovered' => $run->pages_discovered,
            'pages_processed' => $run->pages_processed,
            'documents_upserted' => $run->documents_upserted,
            'started_at' => optional($run->started_at)?->toIso8601String(),
            'finished_at' => optional($run->finished_at)?->toIso8601String(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error_message' => $run->error_message,
        ]);
    }
}
