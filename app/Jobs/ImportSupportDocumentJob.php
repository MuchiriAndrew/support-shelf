<?php

namespace App\Jobs;

use App\Services\Documents\DocumentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportSupportDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $path,
        public array $attributes = [],
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentImportService $documentImportService): void
    {
        $documentImportService->importPath($this->path, $this->attributes);
    }
}
