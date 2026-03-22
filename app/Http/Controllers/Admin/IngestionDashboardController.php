<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrawlRun;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\Source;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class IngestionDashboardController extends Controller
{
    public function __invoke(): View
    {
        $openAiConfigured = filled(config('openai.api_key'));
        $tablesReady = Schema::hasTable('sources')
            && Schema::hasTable('crawl_runs')
            && Schema::hasTable('documents')
            && Schema::hasTable('document_chunks');

        $sourceCount = $tablesReady ? Source::query()->count() : 0;
        $crawlableCount = $tablesReady ? Source::query()->crawlable()->count() : 0;
        $documentCount = $tablesReady ? Document::query()->count() : 0;
        $chunkCount = $tablesReady ? DocumentChunk::query()->count() : 0;
        $indexedChunkCount = $tablesReady ? DocumentChunk::query()->whereNotNull('vector_id')->count() : 0;
        $runCount = $tablesReady ? CrawlRun::query()->count() : 0;

        $recentSources = $tablesReady
            ? Source::query()->withCount('documents')->latest()->take(6)->get()
            : collect();

        $recentDocuments = $tablesReady
            ? Document::query()->with(['source'])->withCount('chunks')->latest()->take(6)->get()
            : collect();

        $recentRuns = $tablesReady
            ? CrawlRun::query()->with('source')->latest()->take(6)->get()
            : collect();

        return view('admin.ingestion', [
            'tablesReady' => $tablesReady,
            'statusCards' => [
                ['label' => 'Support sites', 'value' => $sourceCount, 'status' => $crawlableCount > 0 ? "{$crawlableCount} ready to crawl" : 'Ready to add'],
                ['label' => 'Documents', 'value' => $documentCount, 'status' => $documentCount > 0 ? 'In your library' : 'Waiting for content'],
                ['label' => 'Search passages', 'value' => $chunkCount, 'status' => $chunkCount > 0 ? 'Prepared for answers' : 'Created during ingest'],
                ['label' => 'Search-ready', 'value' => $indexedChunkCount, 'status' => $indexedChunkCount > 0 ? 'Ready for chat' : 'Sync after ingest'],
                ['label' => 'Crawl runs', 'value' => $runCount, 'status' => $runCount > 0 ? 'Recent history saved' : 'No runs yet'],
                ['label' => 'Assistant access', 'value' => $openAiConfigured ? 'Connected' : 'Needs API key', 'status' => $openAiConfigured ? 'Ready' : 'Setup needed'],
            ],
            'recentSources' => $recentSources,
            'recentDocuments' => $recentDocuments,
            'recentRuns' => $recentRuns,
        ]);
    }
}
