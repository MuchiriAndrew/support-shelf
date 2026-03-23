<?php

namespace App\Livewire\Admin;

use App\Jobs\RunSourceCrawlJob;
use App\Jobs\SyncDocumentVectorsJob;
use App\Models\Document;
use App\Models\Source;
use App\Services\Documents\DocumentImportService;
use App\Services\Ingestion\SourceRegistryService;
use App\Services\Retrieval\SupportVectorIndexService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Livewire\Component;

class IngestionWorkspace extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $siteData = [];

    public ?array $documentData = [];

    public function mount(): void
    {
        $this->siteForm->fill([
            'crawl_enabled' => true,
            'crawl_now' => true,
            'max_depth' => (int) config('crawling.max_depth', 2),
            'max_pages' => (int) config('crawling.max_pages', 40),
        ]);

        $this->documentForm->fill([
            'document_type' => 'support_text',
        ]);
    }

    public function siteForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Register a support site')
                    ->description('Save a crawlable help center, product support hub, or policy site.')
                    ->compact()
                    ->schema([
                        Grid::make([
                            'md' => 2,
                        ])->schema([
                            TextInput::make('name')
                                ->label('Site name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Apple AirPods Support'),
                            TextInput::make('url')
                                ->label('Starting URL')
                                ->required()
                                ->url()
                                ->maxLength(2048)
                                ->placeholder('https://support.apple.com/airpods'),
                            TextInput::make('content_selector')
                                ->label('Preferred content selector')
                                ->maxLength(255)
                                ->placeholder('main, article, .content'),
                            Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'paused' => 'Paused',
                                ])
                                ->default('active')
                                ->required(),
                            TextInput::make('max_depth')
                                ->label('Max depth')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(5)
                                ->required(),
                            TextInput::make('max_pages')
                                ->label('Max pages')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(500)
                                ->required(),
                        ]),
                        Grid::make([
                            'md' => 2,
                        ])->schema([
                            Toggle::make('crawl_enabled')
                                ->label('Allow recurring crawling')
                                ->default(true),
                            Toggle::make('crawl_now')
                                ->label('Queue a crawl after saving')
                                ->default(true),
                        ]),
                    ]),
            ])
            ->statePath('siteData');
    }

    public function documentForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import a support document')
                    ->description('Upload manuals, policy files, or troubleshooting guides into the support library.')
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
                        Grid::make([
                            'md' => 2,
                        ])->schema([
                            TextInput::make('title')
                                ->label('Title override')
                                ->maxLength(255)
                                ->placeholder('Returns policy'),
                            TextInput::make('source_name')
                                ->label('Source group')
                                ->maxLength(255)
                                ->placeholder('Store policies'),
                            Select::make('document_type')
                                ->label('Document type')
                                ->required()
                                ->options([
                                    'support_text' => 'Support text',
                                    'manual_pdf' => 'Manual PDF',
                                    'return_policy' => 'Return policy',
                                    'troubleshooting_guide' => 'Troubleshooting guide',
                                    'support_markdown' => 'Markdown article',
                                    'support_page' => 'Support page snapshot',
                                ]),
                        ]),
                    ]),
            ])
            ->statePath('documentData');
    }

    public function createSource(SourceRegistryService $registry): void
    {
        $data = $this->siteForm->getState();

        $metadata = [
            'max_depth' => (int) $data['max_depth'],
            'max_pages' => (int) $data['max_pages'],
        ];

        $source = $registry->registerWebsiteSource([
            'name' => $data['name'],
            'url' => $data['url'],
            'content_selector' => $data['content_selector'] ?: null,
            'crawl_enabled' => (bool) $data['crawl_enabled'],
            'status' => $data['status'],
            'metadata' => $metadata,
        ]);

        if ($data['crawl_now']) {
            RunSourceCrawlJob::dispatch($source->id, 'filament');
        }

        $this->siteForm->fill([
            'crawl_enabled' => true,
            'crawl_now' => true,
            'max_depth' => (int) config('crawling.max_depth', 2),
            'max_pages' => (int) config('crawling.max_pages', 40),
        ]);

        $this->dispatch('knowledge-library-refresh');

        Notification::make()
            ->title('Support site saved')
            ->body($data['crawl_now']
                ? "{$source->name} was saved and queued for crawling."
                : "{$source->name} was saved to the ingestion library.")
            ->success()
            ->send();
    }

    public function importDocument(DocumentImportService $documentImportService): void
    {
        $data = $this->documentForm->getState();
        $file = $data['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            Notification::make()
                ->title('Upload required')
                ->body('Choose a PDF, text file, markdown file, or HTML document before importing.')
                ->warning()
                ->send();

            return;
        }

        try {
            $result = $documentImportService->importUploadedFile($file, [
                'title' => $data['title'] ?: null,
                'document_type' => $data['document_type'],
                'source_name' => $data['source_name'] ?: null,
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

        $this->documentForm->fill([
            'document_type' => 'support_text',
        ]);

        $this->dispatch('knowledge-library-refresh');

        Notification::make()
            ->title('Document imported')
            ->body("{$document->title} was stored with {$result['chunks_count']} searchable passages.")
            ->success()
            ->send();
    }

    public function crawlAllEnabledSites(): void
    {
        $count = Source::query()
            ->crawlable()
            ->pluck('id')
            ->tap(function ($sourceIds): void {
                $sourceIds->each(fn (int $sourceId) => RunSourceCrawlJob::dispatch($sourceId, 'filament'));
            })
            ->count();

        Notification::make()
            ->title($count > 0 ? 'Crawls queued' : 'No crawlable sites')
            ->body($count > 0
                ? "{$count} support site(s) were queued for ingestion."
                : 'Add an active crawl-enabled support site to start a crawl.')
            ->success()
            ->send();
    }

    public function syncPendingVectors(SupportVectorIndexService $indexService): void
    {
        if (! $indexService->isConfigured()) {
            Notification::make()
                ->title('Vector sync unavailable')
                ->body('Configure OpenAI embeddings and the vector store before training the knowledge base.')
                ->warning()
                ->send();

            return;
        }

        $count = Document::query()
            ->whereHas('chunks', fn (Builder $query): Builder => $query->whereNull('vector_id'))
            ->pluck('id')
            ->tap(function ($documentIds): void {
                $documentIds->each(fn (int $documentId) => SyncDocumentVectorsJob::dispatch($documentId));
            })
            ->count();

        Notification::make()
            ->title($count > 0 ? 'Vector sync queued' : 'Knowledge base already trained')
            ->body($count > 0
                ? "{$count} document(s) were queued for indexing in Weaviate."
                : 'There are no pending document chunks waiting for vector sync.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.admin.ingestion-workspace', [
            'workspaceStats' => [
                'sites' => Source::query()->count(),
                'documents' => Document::query()->count(),
                'pendingVectors' => Document::query()
                    ->whereHas('chunks', fn (Builder $query): Builder => $query->whereNull('vector_id'))
                    ->count(),
                'vectorReady' => app(SupportVectorIndexService::class)->isConfigured(),
            ],
        ]);
    }
}
