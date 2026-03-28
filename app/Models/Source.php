<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function scopeOwnedBy(Builder $query, Authenticatable|User|int $user): Builder
    {
        $userId = $user instanceof Authenticatable || $user instanceof User
            ? $user->getAuthIdentifier()
            : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeWebsite(Builder $query): Builder
    {
        return $query
            ->whereNotNull('url')
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('source_type', ['website', 'support_site'])
                    ->orWhereNull('source_type');
            });
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

    /**
     * Get the chunks associated with the source's documents.
     */
    public function documentChunks(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentChunk::class, Document::class);
    }
}
