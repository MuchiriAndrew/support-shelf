<?php

namespace App\Livewire\Admin;

use App\Models\Document;
use App\Services\Retrieval\SupportVectorIndexService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class KnowledgeLibraryTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    protected $listeners = [
        'knowledge-library-refresh' => 'refreshLibrary',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Document::query()
                ->with('source')
                ->withCount('chunks')
                ->withCount([
                    'chunks as indexed_chunks_count' => fn (Builder $query): Builder => $query->whereNotNull('vector_id'),
                ]))
            ->queryStringIdentifier('knowledgeDocuments')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Document $record): string => $record->source?->name ?? 'Uploaded document'),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                TextColumn::make('chunks_count')
                    ->label('Chunks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('indexed_chunks_count')
                    ->label('Weaviate')
                    ->badge()
                    ->formatStateUsing(fn (int $state, Document $record): string => "{$state} / {$record->chunks_count}")
                    ->color(fn (Document $record): string => $record->chunks_count > 0 && $record->indexed_chunks_count === $record->chunks_count ? 'success' : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ready' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('canonical_url')
                    ->label('Canonical URL')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('storage_path')
                    ->label('Stored file')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options(fn (): array => Document::query()
                        ->select('document_type')
                        ->distinct()
                        ->orderBy('document_type')
                        ->pluck('document_type', 'document_type')
                        ->map(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                        ->all()),
                SelectFilter::make('source_id')
                    ->label('Source')
                    ->relationship('source', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('indexed')
                    ->label('Indexed in Weaviate')
                    ->query(fn (Builder $query): Builder => $query->whereHas('chunks', fn (Builder $chunkQuery): Builder => $chunkQuery->whereNotNull('vector_id'))),
                Filter::make('needs_sync')
                    ->label('Needs vector sync')
                    ->query(fn (Builder $query): Builder => $query->whereHas('chunks', fn (Builder $chunkQuery): Builder => $chunkQuery->whereNull('vector_id'))),
            ])
            ->recordActions([
                Action::make('sync_vectors')
                    ->label('Sync vectors')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('primary')
                    ->action(function (Document $record, SupportVectorIndexService $indexService): void {
                        if (! $indexService->isConfigured()) {
                            Notification::make()
                                ->title('Vector sync unavailable')
                                ->body('Configure OpenAI embeddings and Weaviate before syncing vectors.')
                                ->warning()
                                ->send();

                            return;
                        }

                        \App\Jobs\SyncDocumentVectorsJob::dispatch($record->id);

                        Notification::make()
                            ->title('Vector sync queued')
                            ->body("{$record->title} was queued for vector indexing.")
                            ->success()
                            ->send();
                    }),
                Action::make('open_source')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Document $record): ?string => $record->canonical_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => filled($record->canonical_url)),
            ])
            ->emptyStateHeading('No documents yet')
            ->emptyStateDescription('Imported manuals, crawled support pages, and policy files will appear here once added.')
            ->paginated([10, 25, 50]);
    }

    public function refreshLibrary(): void
    {
        $this->resetTable();
    }

    public function render(): View
    {
        return view('livewire.admin.knowledge-library-table');
    }
}
