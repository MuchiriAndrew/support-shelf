<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Document extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'source_id',
        'title',
        'document_type',
        'language',
        'storage_disk',
        'storage_path',
        'canonical_url',
        'checksum',
        'content_text',
        'token_estimate',
        'status',
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
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source associated with the document.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Get the chunks for the document.
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function scopeOwnedBy(Builder $query, Authenticatable|User|int $user): Builder
    {
        $userId = $user instanceof Authenticatable || $user instanceof User
            ? $user->getAuthIdentifier()
            : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeUploaded(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNull('source_id')
                ->orWhereHas('source', fn (Builder $query): Builder => $query->where('source_type', 'uploaded_collection'));
        });
    }
}
