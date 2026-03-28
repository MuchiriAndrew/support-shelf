<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssistantConversation extends Model
{
    protected $table = 'support_conversations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'session_token',
        'title',
        'status',
        'model',
        'last_response_id',
        'last_message_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'conversation_id');
    }

    public function scopeOwnedBy(Builder $query, Authenticatable|User|int $user): Builder
    {
        $userId = $user instanceof Authenticatable || $user instanceof User
            ? $user->getAuthIdentifier()
            : $user;

        return $query->where('user_id', $userId);
    }
}
