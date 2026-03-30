<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\IngestionActivityChart;
use App\Filament\Widgets\IngestionOverviewStats;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $title = 'Analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            IngestionOverviewStats::class,
            IngestionActivityChart::class,
        ];
    }
}
