<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlRun extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'source_id',
        'status',
        'triggered_by',
        'started_at',
        'finished_at',
        'pages_discovered',
        'pages_processed',
        'documents_upserted',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the source associated with the crawl run.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
