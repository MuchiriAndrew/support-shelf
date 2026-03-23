<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class KnowledgeLibrary extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Knowledge Library';

    protected static ?string $title = 'Knowledge Library';

    protected static ?string $slug = 'knowledge-library';

    protected static ?int $navigationSort = 1;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'filament.pages.knowledge-library';
}
