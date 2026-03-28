<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Documents\DocumentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class ImportKnowledgeDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int $userId,
        public string $path,
        public array $attributes = [],
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentImportService $documentImportService): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            throw new RuntimeException("Cannot import a document because user [{$this->userId}] no longer exists.");
        }

        $documentImportService->importPath($user, $this->path, $this->attributes);
    }
}
