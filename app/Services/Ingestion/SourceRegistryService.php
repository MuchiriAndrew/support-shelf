<?php

namespace App\Services\Ingestion;

use App\Models\Source;
use App\Support\SupportActivityLog;
use Illuminate\Support\Arr;

class SourceRegistryService
{
    /**
     * Register or update a crawlable website source.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function registerWebsiteSource(array $attributes): Source
    {
        $url = $this->normalizeUrl((string) $attributes['url']);
        $domain = parse_url($url, PHP_URL_HOST);
        $source = Source::updateOrCreate(
            ['url' => $url],
            [
                'name' => (string) $attributes['name'],
                'source_type' => (string) ($attributes['source_type'] ?? 'support_site'),
                'domain' => is_string($domain) ? strtolower($domain) : null,
                'content_selector' => Arr::get($attributes, 'content_selector'),
                'crawl_enabled' => (bool) ($attributes['crawl_enabled'] ?? true),
                'status' => (string) ($attributes['status'] ?? 'active'),
                'metadata' => Arr::get($attributes, 'metadata', []),
            ],
        );

        SupportActivityLog::info('Support source registered', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'source_type' => $source->source_type,
            'source_url' => $source->url,
            'source_domain' => $source->domain,
            'crawl_enabled' => $source->crawl_enabled,
            'status' => $source->status,
            'was_created' => $source->wasRecentlyCreated,
        ]);

        return $source;
    }

    /**
     * Register or reuse a logical source for uploaded files.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function registerUploadedSource(string $name, array $metadata = []): Source
    {
        $source = Source::firstOrCreate(
            [
                'name' => $name,
                'source_type' => 'uploaded_document',
            ],
            [
                'crawl_enabled' => false,
                'status' => 'active',
                'metadata' => $metadata,
            ],
        );

        SupportActivityLog::info('Uploaded document source resolved', [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'was_created' => $source->wasRecentlyCreated,
        ]);

        return $source;
    }

    /**
     * Normalize a URL before storing it.
     */
    protected function normalizeUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }
}
