<?php

namespace App\Filament\Widgets;

use App\Services\Ingestion\IngestionAnalyticsService;
use Filament\Widgets\ChartWidget;

class IngestionActivityChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Ingestion activity';

    protected ?string $description = 'Daily crawl, document, and vector indexing throughput over the last week.';

    protected function getData(): array
    {
        $series = app(IngestionAnalyticsService::class)->dailySeries(7);

        return [
            'datasets' => [
                [
                    'label' => 'Crawl runs',
                    'data' => $series['crawl_runs'],
                    'borderColor' => '#60a5fa',
                    'backgroundColor' => 'rgba(96, 165, 250, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Documents',
                    'data' => $series['documents'],
                    'borderColor' => '#c084fc',
                    'backgroundColor' => 'rgba(192, 132, 252, 0.12)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Indexed chunks',
                    'data' => $series['indexed_chunks'],
                    'borderColor' => '#34d399',
                    'backgroundColor' => 'rgba(52, 211, 153, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
