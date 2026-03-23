<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageIngestion extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowUp;

    protected static ?string $navigationLabel = 'Knowledge Ingestion';

    protected static ?string $title = 'Knowledge Ingestion';

    protected static ?string $slug = 'knowledge-ingestion';

    protected static ?int $navigationSort = 0;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'filament.pages.manage-ingestion';
}
