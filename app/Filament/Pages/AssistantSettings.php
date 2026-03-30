<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class AssistantSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'My Assistant';

    protected static ?string $title = 'My Assistant';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?string $slug = 'assistant-settings';

    protected static ?int $navigationSort = 2;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'filament.pages.assistant-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'assistant_name' => $user?->assistant_name,
            'assistant_instructions' => $user?->assistant_instructions,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('assistant_name')
                    ->label('Assistant name')
                    ->placeholder(auth()->user()?->assistantDisplayName() ?? 'My Assistant')
                    ->maxLength(120)
                    ->helperText('This is the name shown across chat and your workspace.'),
                Textarea::make('assistant_instructions')
                    ->label('Custom instructions')
                    ->rows(10)
                    ->maxLength(6000)
                    ->placeholder('Example: Answer like a research assistant. Be concise, structured, and transparent when context is incomplete.')
                    ->helperText('These instructions shape tone and behavior while the assistant still answers from your own knowledge base.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = auth()->user();

        $user?->forceFill([
            'assistant_name' => trim((string) ($state['assistant_name'] ?? '')) ?: null,
            'assistant_instructions' => trim((string) ($state['assistant_instructions'] ?? '')) ?: null,
        ])->save();

        Notification::make()
            ->title('Assistant settings updated')
            ->success()
            ->send();
    }
}
