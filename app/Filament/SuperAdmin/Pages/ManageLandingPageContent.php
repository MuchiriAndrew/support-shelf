<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\LandingPageContent;
use App\Support\LandingPageDefaults;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ManageLandingPageContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'CMS';

    protected static ?string $navigationLabel = 'Landing Page';

    protected static ?string $title = 'Landing Page Content';

    protected static ?string $slug = 'cms/landing-page';

    protected static ?int $navigationSort = 10;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected string $view = 'filament.super-admin.pages.manage-landing-page-content';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $content = LandingPageContent::query()->firstOrCreate(
            ['slug' => 'home'],
            LandingPageDefaults::content(),
        );

        $resolved = $content->content();

        $this->form->fill([
            'hero' => $resolved['hero'],
            'metrics' => $resolved['metrics'],
            'pillars' => $resolved['pillars'],
            'workflow' => $resolved['workflow'],
            'showcases' => $resolved['showcases'],
            'proof_points' => collect($resolved['proof_points'])->map(fn (string $value): array => ['value' => $value])->all(),
            'cta' => $resolved['cta'],
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Hero')
                    ->schema([
                        TextInput::make('hero.kicker')
                            ->label('Eyebrow')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('hero.title')
                            ->label('Headline')
                            ->required()
                            ->rows(3)
                            ->maxLength(280),
                        Textarea::make('hero.description')
                            ->label('Intro copy')
                            ->required()
                            ->rows(4)
                            ->maxLength(800),
                    ])
                    ->columns(1),
                Repeater::make('metrics')
                    ->label('Hero metrics')
                    ->schema([
                        TextInput::make('label')->required()->maxLength(120),
                        TextInput::make('value')->required()->maxLength(180),
                    ])
                    ->default(LandingPageDefaults::content()['metrics'])
                    ->columns(2)
                    ->minItems(3)
                    ->maxItems(6)
                    ->reorderable(false),
                Repeater::make('pillars')
                    ->label('Value pillars')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(160),
                        Textarea::make('description')->required()->rows(3)->maxLength(500),
                    ])
                    ->default(LandingPageDefaults::content()['pillars'])
                    ->columns(1)
                    ->minItems(3)
                    ->maxItems(6),
                Repeater::make('workflow')
                    ->label('Workflow steps')
                    ->schema([
                        TextInput::make('title')->required()->maxLength(160),
                        Textarea::make('description')->required()->rows(3)->maxLength(500),
                    ])
                    ->default(LandingPageDefaults::content()['workflow'])
                    ->columns(1)
                    ->minItems(3)
                    ->maxItems(6),
                Repeater::make('showcases')
                    ->label('Showcase sections')
                    ->schema([
                        TextInput::make('eyebrow')->required()->maxLength(120),
                        TextInput::make('title')->required()->maxLength(180),
                        Textarea::make('description')->required()->rows(3)->maxLength(500),
                        FileUpload::make('video_path')
                            ->label('Video')
                            ->disk('public')
                            ->directory('landing-page/videos')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                            ->maxSize(256000),
                        TextInput::make('placeholder')->required()->maxLength(180),
                    ])
                    ->default(LandingPageDefaults::content()['showcases'])
                    ->columns(1)
                    ->minItems(2)
                    ->maxItems(6),
                Repeater::make('proof_points')
                    ->label('Proof points')
                    ->schema([
                        TextInput::make('value')->required()->maxLength(220),
                    ])
                    ->minItems(3)
                    ->maxItems(8),
                Section::make('Closing call to action')
                    ->schema([
                        TextInput::make('cta.kicker')
                            ->label('Eyebrow')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('cta.title')
                            ->label('Headline')
                            ->required()
                            ->rows(3)
                            ->maxLength(260),
                        Textarea::make('cta.description')
                            ->label('Description')
                            ->required()
                            ->rows(4)
                            ->maxLength(600),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        LandingPageContent::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'hero' => $state['hero'] ?? [],
                'metrics' => $state['metrics'] ?? [],
                'pillars' => $state['pillars'] ?? [],
                'workflow' => $state['workflow'] ?? [],
                'showcases' => $state['showcases'] ?? [],
                'proof_points' => collect($state['proof_points'] ?? [])
                    ->pluck('value')
                    ->filter()
                    ->values()
                    ->all(),
                'cta' => $state['cta'] ?? [],
            ],
        );

        Notification::make()
            ->title('Landing page updated')
            ->body('The public homepage now reflects your CMS content.')
            ->success()
            ->send();
    }
}
