<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageBilling extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Revenue';

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Billing';

    protected static ?string $slug = 'billing';

    protected static ?int $navigationSort = 20;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'filament.super-admin.pages.manage-billing';
}
