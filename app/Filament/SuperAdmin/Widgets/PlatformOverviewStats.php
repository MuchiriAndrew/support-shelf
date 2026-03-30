<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Services\Admin\PlatformAnalyticsService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverviewStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Platform overview';

    protected ?string $description = 'See how many customers, sources, and conversations are active across the SaaS platform.';

    protected function getStats(): array
    {
        $totals = app(PlatformAnalyticsService::class)->totals();
        $series = app(PlatformAnalyticsService::class)->dailySeries(7);

        return [
            Stat::make('Registered users', number_format($totals['users']))
                ->description("{$totals['customers']} customers, {$totals['super_admins']} super admins")
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->chart($series['users'])
                ->color('primary'),
            Stat::make('Knowledge sources', number_format($totals['sources']))
                ->description('All websites and document collections owned by customers')
                ->descriptionIcon('heroicon-m-globe-alt', IconPosition::Before)
                ->chart($series['documents'])
                ->color('info'),
            Stat::make('Documents stored', number_format($totals['documents']))
                ->description('Files and crawled pages available for retrieval')
                ->descriptionIcon('heroicon-m-document-text', IconPosition::Before)
                ->chart($series['documents'])
                ->color('warning'),
            Stat::make('Conversations', number_format($totals['conversations']))
                ->description('Private assistant chats happening across accounts')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right', IconPosition::Before)
                ->chart($series['conversations'])
                ->color('success'),
        ];
    }
}
