<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AssistantSettings;
use App\Filament\Pages\KnowledgeLibrary;
use App\Filament\Pages\ManageIngestion;
use App\Filament\SuperAdmin\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('super-admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName(config('assistant.brand.name'))
            ->font('Outfit')
            ->maxContentWidth(Width::Full)
            ->login()
            ->pages([
                Dashboard::class,
                AssistantSettings::class,
                ManageIngestion::class,
                KnowledgeLibrary::class,
            ])
            ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\Filament\SuperAdmin\Pages')
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\Filament\SuperAdmin\Resources')
            ->discoverWidgets(in: app_path('Filament/SuperAdmin/Widgets'), for: 'App\Filament\SuperAdmin\Widgets')
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Access Control')
                    ->navigationSort(50)
                    ->simpleResourcePermissionView(),
            ])
            ->navigationItems([
                NavigationItem::make('Open workspace')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(fn (): string => route('filament.admin.pages.dashboard'))
                    ->sort(998),
                NavigationItem::make('Back to website')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url(fn (): string => route('home'))
                    ->sort(999),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
