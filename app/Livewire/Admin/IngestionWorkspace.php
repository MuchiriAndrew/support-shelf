<?php

namespace App\Livewire\Admin;

use App\Jobs\RunSourceCrawlJob;
use App\Models\User;
use App\Services\Documents\DocumentImportService;
use App\Services\Ingestion\SourceRegistryService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Livewire\Component;
use RuntimeException;

class IngestionWorkspace extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $siteData = [];

    public ?array $documentData = [];

    public function mount(): void
    {
        $this->siteForm->fill();
        $this->documentForm->fill();
    }

    public function siteForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Register a website source')
                    ->description('Save a website and it will be crawled into your private knowledge base automatically.')
                    ->compact()
                    ->schema([
                        Grid::make([
                            'md' => 2,
                        ])->schema([
                            TextInput::make('name')
                                ->label('Website name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Company docs'),
                            TextInput::make('url')
                                ->label('Website URL')
                                ->required()
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://docs.example.com'),
                        ]),
                    ]),
            ])
            ->statePath('siteData');
    }

    public function documentForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import a knowledge document')
                    ->description('Upload PDFs, text files, markdown, or HTML into your private knowledge base.')
                    ->compact()
                    ->schema([
                        FileUpload::make('file')
                            ->label('Document')
                            ->required()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'text/plain',
                                'text/markdown',
                                'text/html',
                            ])
                            ->storeFiles(false)
                            ->previewable(false)
                            ->maxSize(10240),
                        TextInput::make('title')
                            ->label('Title override')
                            ->maxLength(255)
                            ->placeholder('Returns policy'),
                    ]),
            ])
            ->statePath('documentData');
    }

    public function createSource(SourceRegistryService $registry): void
    {
        $data = $this->siteForm->getState();
        /** @var User $user */
        $user = auth()->user();

        $source = $registry->registerWebsiteSource($user, [
            'name' => $data['name'],
            'url' => $data['url'],
            'crawl_enabled' => true,
            'status' => 'active',
            'metadata' => [
                // Keep the form simple while still crawling much more broadly than the old shallow defaults.
                'max_depth' => $this->defaultWebsiteMaxDepth(),
                'max_pages' => $this->defaultWebsiteMaxPages(),
            ],
        ]);

        RunSourceCrawlJob::dispatch($source->id, 'filament');

        $this->siteForm->fill();
        $this->dispatch('knowledge-library-refresh');

        Notification::make()
            ->title('Website source saved')
            ->body("{$source->name} was saved and queued for crawling.")
            ->success()
            ->send();
    }

    public function importDocument(DocumentImportService $documentImportService): void
    {
        $data = $this->documentForm->getState();
        $file = $data['file'] ?? null;
        /** @var User $user */
        $user = auth()->user();

        if (! $file instanceof UploadedFile) {
            Notification::make()
                ->title('Upload required')
                ->body('Choose a PDF, text file, markdown file, or HTML document before importing.')
                ->warning()
                ->send();

            return;
        }

        try {
            $result = $documentImportService->importUploadedFile($user, $file, [
                'title' => $data['title'] ?: null,
            ]);
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('Import failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $document = $result['document'];

        $this->documentForm->fill();
        $this->dispatch('knowledge-library-refresh');

        Notification::make()
            ->title('Document imported')
            ->body("{$document->title} was stored with {$result['chunks_count']} searchable passages.")
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.admin.ingestion-workspace');
    }

    protected function defaultWebsiteMaxDepth(): int
    {
        return max(5, (int) config('crawling.max_depth', 5));
    }

    protected function defaultWebsiteMaxPages(): int
    {
        return max(500, (int) config('crawling.max_pages', 500));
    }
}
