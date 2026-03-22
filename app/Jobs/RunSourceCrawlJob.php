<?php

namespace App\Jobs;

use App\Models\Source;
use App\Services\Crawling\SupportSiteCrawler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(SupportSiteCrawler $crawler): void
    {
        $source = Source::query()->findOrFail($this->sourceId);

        $crawler->crawlSource($source, $this->triggeredBy);
    }
}
