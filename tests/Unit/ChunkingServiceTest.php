<?php

namespace Tests\Unit;

use App\Services\Documents\ChunkingService;
use App\Services\Documents\TokenEstimator;
use PHPUnit\Framework\TestCase;

class ChunkingServiceTest extends TestCase
{
    public function test_it_splits_large_text_into_multiple_chunks(): void
    {
        $service = new ChunkingService(new TokenEstimator());
        $paragraph = str_repeat('This support paragraph explains setup and troubleshooting steps. ', 40);
        $text = implode("\n\n", array_fill(0, 6, trim($paragraph)));

        $chunks = $service->chunk($text, 120, 20);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertArrayHasKey('content', $chunks[0]);
        $this->assertArrayHasKey('token_estimate', $chunks[0]);
    }
}
