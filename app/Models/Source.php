<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'source_type',
        'domain',
        'url',
        'content_selector',
        'crawl_enabled',
        'status',
        'metadata',
        'last_crawled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'crawl_enabled' => 'boolean',
            'metadata' => 'array',
            'last_crawled_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to crawl-enabled sources.
     */
    public function scopeCrawlable(Builder $query): Builder
    {
        return $query
            ->where('crawl_enabled', true)
            ->whereNotNull('url')
            ->where('status', 'active');
    }

    /**
     * Get the crawl runs for the source.
     */
    public function crawlRuns(): HasMany
    {
        return $this->hasMany(CrawlRun::class);
    }

    /**
     * Get the documents for the source.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
