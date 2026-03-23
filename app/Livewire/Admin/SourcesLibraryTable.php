<?php

namespace App\Livewire\Admin;

use App\Jobs\RunSourceCrawlJob;
use App\Jobs\SyncDocumentVectorsJob;
use App\Models\Source;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class SourcesLibraryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected $listeners = [
        'knowledge-library-refresh' => 'refreshLibrary',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Source::query()
                ->withCount(['documents', 'crawlRuns', 'documentChunks'])
                ->withCount([
                    'documentChunks as indexed_chunks_count' => fn (Builder $query): Builder => $query->whereNotNull('vector_id'),
                ]))
            ->queryStringIdentifier('knowledgeSites')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Site')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Source $record): ?string => $record->domain ?: $record->url),
                TextColumn::make('source_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                IconColumn::make('crawl_enabled')
                    ->label('Crawl')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->label('Documents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('indexed_chunks_count')
                    ->label('Vector sync')
                    ->badge()
                    ->color(fn (Source $record): string => $record->document_chunks_count > 0 && $record->indexed_chunks_count === $record->document_chunks_count ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state, Source $record): string => "{$state} / {$record->document_chunks_count}"),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('last_crawled_at')
                    ->label('Last crawl')
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'paused' => 'Paused',
                    ]),
                SelectFilter::make('source_type')
                    ->options(fn (): array => Source::query()
                        ->select('source_type')
                        ->distinct()
                        ->orderBy('source_type')
                        ->pluck('source_type', 'source_type')
                        ->map(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                        ->all()),
                TernaryFilter::make('crawl_enabled')
                    ->label('Crawl enabled'),
                Filter::make('indexed')
                    ->label('Indexed in Weaviate')
                    ->query(fn (Builder $query): Builder => $query->whereHas('documentChunks', fn (Builder $chunkQuery): Builder => $chunkQuery->whereNotNull('vector_id'))),
            ])
            ->recordActions([
                Action::make('crawl_now')
                    ->label('Crawl now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (Source $record): void {
                        RunSourceCrawlJob::dispatch($record->id, 'filament');

                        Notification::make()
                            ->title('Crawl queued')
                            ->body("{$record->name} is queued for a fresh crawl.")
                            ->success()
                            ->send();
                    }),
                Action::make('train_vectors')
                    ->label('Sync vectors')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('gray')
                    ->action(function (Source $record): void {
                        $queued = $record->documents()
                            ->whereHas('chunks', fn (Builder $query): Builder => $query->whereNull('vector_id'))
                            ->pluck('id')
                            ->tap(function ($documentIds): void {
                                $documentIds->each(fn (int $documentId) => SyncDocumentVectorsJob::dispatch($documentId));
                            })
                            ->count();

                        Notification::make()
                            ->title($queued > 0 ? 'Vector sync queued' : 'No pending vectors')
                            ->body($queued > 0
                                ? "{$queued} document(s) from {$record->name} were queued for indexing."
                                : "{$record->name} is already up to date in the vector store.")
                            ->success()
                            ->send();
                    }),
                Action::make('open_site')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Source $record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->visible(fn (Source $record): bool => filled($record->url)),
            ])
            ->emptyStateHeading('No support sites yet')
            ->emptyStateDescription('Add a crawlable help center or support knowledge site to start building the library.')
            ->paginated([10, 25, 50]);
    }

    public function refreshLibrary(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.admin.sources-library-table');
    }
}
