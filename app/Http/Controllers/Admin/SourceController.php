<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use App\Services\Crawling\SupportSiteCrawler;
use App\Services\Ingestion\SourceRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SourceController extends Controller
{
    /**
     * Persist a new support source.
     */
    public function store(Request $request, SourceRegistryService $registry): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048'],
            'content_selector' => ['nullable', 'string', 'max:255'],
            'max_depth' => ['nullable', 'integer', 'min:0', 'max:5'],
            'max_pages' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $metadata = [];

        if (isset($validated['max_depth'])) {
            $metadata['max_depth'] = (int) $validated['max_depth'];
        }

        if (isset($validated['max_pages'])) {
            $metadata['max_pages'] = (int) $validated['max_pages'];
        }

        $source = $registry->registerWebsiteSource([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'content_selector' => $validated['content_selector'] ?? null,
            'metadata' => $metadata,
        ]);

        return back()->with('ingestion_success', "Saved source [{$source->id}] {$source->name}.");
    }

    /**
     * Crawl a registered support source immediately.
     */
    public function crawl(Source $source, SupportSiteCrawler $crawler): RedirectResponse
    {
        try {
            $run = $crawler->crawlSource($source, 'dashboard');
        } catch (Throwable $exception) {
            return back()->with('ingestion_error', "Crawl failed: {$exception->getMessage()}");
        }

        return back()->with(
            'ingestion_success',
            "Crawl completed for {$source->name}: {$run->pages_processed} pages processed, {$run->documents_upserted} documents changed."
        );
    }
}
