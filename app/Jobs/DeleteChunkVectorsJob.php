<?php

namespace App\Jobs;

use App\Contracts\VectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteChunkVectorsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $vectorIds
     */
    public function __construct(
        public array $vectorIds,
    ) {
    }

    public function handle(VectorStore $vectorStore): void
    {
        if (! $vectorStore->isConfigured()) {
            return;
        }

        $vectorStore->deleteVectors($this->vectorIds);
    }
}
