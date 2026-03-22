<?php

namespace App\Services\Ingestion;

use App\Models\Source;
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

        return Source::updateOrCreate(
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
    }

    /**
     * Register or reuse a logical source for uploaded files.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function registerUploadedSource(string $name, array $metadata = []): Source
    {
        return Source::firstOrCreate(
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
    }

    /**
     * Normalize a URL before storing it.
     */
    protected function normalizeUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }
}
