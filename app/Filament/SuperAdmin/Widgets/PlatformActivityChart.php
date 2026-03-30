<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Services\Admin\PlatformAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformActivityChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Platform activity';

    protected ?string $description = 'Registrations, content growth, and private conversations over the last week.';

    protected function getData(): array
    {
        $series = app(PlatformAnalyticsService::class)->dailySeries(7);

        return [
            'datasets' => [
                [
                    'label' => 'New users',
                    'data' => $series['users'],
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
                    'label' => 'Conversations',
                    'data' => $series['conversations'],
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
