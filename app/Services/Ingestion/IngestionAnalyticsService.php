<?php

namespace App\Services\Ingestion;

use App\Models\CrawlRun;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Source;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IngestionAnalyticsService
{
    public function tablesReady(): bool
    {
        return Schema::hasTable('sources')
            && Schema::hasTable('crawl_runs')
            && Schema::hasTable('documents')
            && Schema::hasTable('document_chunks');
    }

    /**
     * @return array{
     *     sources:int,
     *     crawlable_sources:int,
     *     documents:int,
     *     chunks:int,
     *     indexed_chunks:int,
     *     pending_chunks:int,
     *     crawl_runs:int
     * }
     */
    public function totals(): array
    {
        if (! $this->tablesReady()) {
            return [
                'sources' => 0,
                'crawlable_sources' => 0,
                'documents' => 0,
                'chunks' => 0,
                'indexed_chunks' => 0,
                'pending_chunks' => 0,
                'crawl_runs' => 0,
            ];
        }

        $chunkCount = DocumentChunk::query()->count();
        $indexedChunkCount = DocumentChunk::query()->whereNotNull('vector_id')->count();

        return [
            'sources' => Source::query()->count(),
            'crawlable_sources' => Source::query()->crawlable()->count(),
            'documents' => Document::query()->count(),
            'chunks' => $chunkCount,
            'indexed_chunks' => $indexedChunkCount,
            'pending_chunks' => max(0, $chunkCount - $indexedChunkCount),
            'crawl_runs' => CrawlRun::query()->count(),
        ];
    }

    /**
     * @return array{
     *     labels: list<string>,
     *     crawl_runs: list<int>,
     *     documents: list<int>,
     *     indexed_chunks: list<int>
     * }
     */
    public function dailySeries(int $days = 7): array
    {
        $days = max(1, $days);
        $window = collect(range($days - 1, 0))
            ->map(fn (int $offset): Carbon => now()->copy()->subDays($offset)->startOfDay())
            ->values();

        if (! $this->tablesReady()) {
            return [
                'labels' => $window->map(fn (Carbon $day): string => $day->format('M j'))->all(),
                'crawl_runs' => array_fill(0, $window->count(), 0),
                'documents' => array_fill(0, $window->count(), 0),
                'indexed_chunks' => array_fill(0, $window->count(), 0),
            ];
        }

        return [
            'labels' => $window->map(fn (Carbon $day): string => $day->format('M j'))->all(),
            'crawl_runs' => $this->mapSeries(
                $window,
                CrawlRun::query(),
                'created_at',
            ),
            'documents' => $this->mapSeries(
                $window,
                Document::query(),
                'created_at',
            ),
            'indexed_chunks' => $this->mapSeries(
                $window,
                DocumentChunk::query()->whereNotNull('vector_id'),
                'updated_at',
            ),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  Collection<int, CarbonInterface>  $window
     * @return list<int>
     */
    protected function mapSeries(Collection $window, $query, string $column): array
    {
        $start = $window->first()?->copy()->startOfDay();
        $end = $window->last()?->copy()->endOfDay();

        if (! $start || ! $end) {
            return [];
        }

        /** @var array<string, int> $counts */
        $counts = $query
            ->whereBetween($column, [$start, $end])
            ->selectRaw('DATE(' . DB::getQueryGrammar()->wrap($column) . ') as summary_date')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('summary_date')
            ->pluck('aggregate', 'summary_date')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();

        return $window
            ->map(fn (CarbonInterface $day): int => $counts[$day->toDateString()] ?? 0)
            ->all();
    }
}
