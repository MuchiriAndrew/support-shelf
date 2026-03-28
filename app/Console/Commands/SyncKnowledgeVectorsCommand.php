<?php

namespace App\Console\Commands;

use App\Models\Source;
use App\Models\User;
use App\Services\Retrieval\KnowledgeVectorIndexService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class SyncKnowledgeVectorsCommand extends Command
{
    protected $signature = 'knowledge:vectors:sync
                            {source? : A source id, name, or URL}
                            {--all : Sync all available sources}
                            {--force : Re-embed chunks even if vector ids already exist}
                            {--limit=50 : Maximum documents to sync in one run}
                            {--user= : Optional user id or email to scope the sync}';

    protected $description = 'Generate embeddings and sync user knowledge chunks into the configured vector store';

    public function handle(KnowledgeVectorIndexService $indexService): int
    {
        if (! $indexService->isConfigured()) {
            $this->error('Vector sync is not configured. Set OPENAI_API_KEY, OPENAI_EMBEDDING_MODEL, and WEAVIATE_URL first.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');
        $sources = $this->resolveSources();
        $user = $this->resolveUser();

        if ($this->option('all') || $this->argument('source') === null) {
            $this->line("Syncing up to {$limit} documents across all sources...");

            try {
                if (! $user) {
                    $this->error('Provide --user=<id-or-email> when syncing all vectors so the run is scoped to one workspace.');

                    return self::FAILURE;
                }

                $summary = $indexService->syncPendingDocuments($user, null, $limit, $force);
            } catch (Throwable $exception) {
                $this->error("Vector sync failed: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $this->info("Synced {$summary['chunks_indexed']} chunks across {$summary['documents_indexed']} documents.");

            return self::SUCCESS;
        }

        if ($sources->isEmpty()) {
            $this->warn('No matching sources were found.');

            return self::FAILURE;
        }

        $totals = [
            'documents_indexed' => 0,
            'chunks_indexed' => 0,
        ];

        foreach ($sources as $source) {
            $this->line("Syncing vectors for [{$source->id}] {$source->name}...");

            try {
                $summary = $indexService->syncPendingDocuments($source->user_id, $source, $limit, $force);
            } catch (Throwable $exception) {
                $this->error("Failed: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $totals['documents_indexed'] += $summary['documents_indexed'];
            $totals['chunks_indexed'] += $summary['chunks_indexed'];

            $this->info("Completed: {$summary['chunks_indexed']} chunks synced across {$summary['documents_indexed']} documents.");
        }

        $this->line("Total: {$totals['chunks_indexed']} chunks synced across {$totals['documents_indexed']} documents.");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Source>
     */
    protected function resolveSources()
    {
        $sourceArgument = $this->argument('source');
        $user = $this->resolveUser();

        if ($this->option('all') || $sourceArgument === null) {
            return Source::query()
                ->when($user, fn ($query) => $query->ownedBy($user))
                ->get();
        }

        return Source::query()
            ->when($user, fn ($query) => $query->ownedBy($user))
            ->where(function ($query) use ($sourceArgument): void {
                if (is_numeric($sourceArgument)) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), (int) $sourceArgument);
                }

                $query
                    ->orWhere('name', $sourceArgument)
                    ->orWhere('url', $sourceArgument);
            })
            ->get();
    }

    protected function resolveUser(): ?User
    {
        $value = trim((string) $this->option('user'));

        if ($value === '') {
            return null;
        }

        return User::query()
            ->when(is_numeric($value), fn ($query) => $query->whereKey((int) $value), fn ($query) => $query->where('email', $value))
            ->firstOr(fn () => throw new RuntimeException("No user found for [{$value}]."));
    }
}
