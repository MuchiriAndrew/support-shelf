<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Documents\DocumentImportService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportKnowledgeDocumentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'knowledge:import
                            {path : Path to a PDF, text, markdown, or HTML file}
                            {--title= : Optional display title}
                            {--type= : Optional logical document type}
                            {--source= : Optional source name for grouping uploads}
                            {--user= : Required user id or email that will own the imported document}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a local document into a user knowledge workspace';

    /**
     * Execute the console command.
     */
    public function handle(DocumentImportService $documentImportService): int
    {
        try {
            $user = $this->resolveUser();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $path = (string) $this->argument('path');

        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        try {
            $result = $documentImportService->importPath($user, $path, [
                'title' => $this->option('title') ?: null,
                'document_type' => $this->option('type') ?: null,
                'source_name' => $this->option('source') ?: null,
            ]);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $document = $result['document'];

        $this->info(sprintf(
            'Imported document [%d] %s with %d chunks.',
            $document->id,
            $document->title,
            $result['chunks_count'],
        ));

        return self::SUCCESS;
    }

    protected function resolveUser(): User
    {
        $value = trim((string) $this->option('user'));

        if ($value === '') {
            throw new RuntimeException('Provide --user=<id-or-email> so the document is assigned to a workspace.');
        }

        return User::query()
            ->when(is_numeric($value), fn ($query) => $query->whereKey((int) $value), fn ($query) => $query->where('email', $value))
            ->firstOr(fn () => throw new RuntimeException("No user found for [{$value}]."));
    }
}
