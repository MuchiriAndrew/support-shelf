<?php

namespace App\Console\Commands;

use App\Services\Documents\DocumentImportService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportSupportDocumentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:import
                            {path : Path to a PDF, text, markdown, or HTML file}
                            {--title= : Optional display title}
                            {--type= : Optional logical document type}
                            {--source= : Optional source name for grouping uploads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a local support document into the ingestion pipeline';

    /**
     * Execute the console command.
     */
    public function handle(DocumentImportService $documentImportService): int
    {
        $path = (string) $this->argument('path');

        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        try {
            $result = $documentImportService->importPath($path, [
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
}
