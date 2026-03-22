<?php

namespace App\Jobs;

use App\Services\Retrieval\SupportVectorIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncDocumentVectorsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $documentId,
        public bool $force = false,
    ) {
    }

    public function handle(SupportVectorIndexService $indexService): void
    {
        if (! $indexService->isConfigured()) {
            return;
        }

        $indexService->syncDocument($this->documentId, $this->force);
    }
}
