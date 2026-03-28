<?php

namespace App\Filament\Widgets;

use App\Services\Ingestion\IngestionAnalyticsService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IngestionOverviewStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Knowledge base health';

    protected ?string $description = 'Track ingestion volume, vector readiness, and how much private knowledge is available to your assistant.';

    protected function getStats(): array
    {
        $totals = app(IngestionAnalyticsService::class)->totals();
        $series = app(IngestionAnalyticsService::class)->dailySeries(7);

        return [
            Stat::make('Website sources', number_format($totals['sources']))
                ->description("{$totals['crawlable_sources']} ready to crawl")
                ->descriptionIcon('heroicon-m-globe-alt', IconPosition::Before)
                ->chart($series['crawl_runs'])
                ->color('primary'),
            Stat::make('Documents', number_format($totals['documents']))
                ->description('Stored manuals, guides, and policies')
                ->descriptionIcon('heroicon-m-document-text', IconPosition::Before)
                ->chart($series['documents'])
                ->color('info'),
            Stat::make('Search passages', number_format($totals['chunks']))
                ->description('Chunked and prepared for retrieval')
                ->descriptionIcon('heroicon-m-rectangle-stack', IconPosition::Before)
                ->chart($series['documents'])
                ->color('warning'),
            Stat::make('Indexed in Weaviate', number_format($totals['indexed_chunks']))
                ->description($totals['pending_chunks'] > 0 ? "{$totals['pending_chunks']} still pending sync" : 'Everything is search-ready')
                ->descriptionIcon(
                    $totals['pending_chunks'] > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-check-badge',
                    IconPosition::Before,
                )
                ->chart($series['indexed_chunks'])
                ->color($totals['pending_chunks'] > 0 ? 'warning' : 'success'),
        ];
    }
}
