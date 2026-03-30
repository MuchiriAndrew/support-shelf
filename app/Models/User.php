<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'assistant_name', 'assistant_instructions', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            if (! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles')) {
                return;
            }

            if ($user->roles()->exists()) {
                return;
            }

            $roleName = $user->getKey() === 1
                ? self::ROLE_SUPER_ADMIN
                : self::ROLE_CUSTOMER;

            if ($role = Role::query()->where('name', $roleName)->first()) {
                $user->assignRole($role);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'superadmin' => $this->hasRole(self::ROLE_SUPER_ADMIN),
            'admin' => $this->hasAnyRole([self::ROLE_CUSTOMER, self::ROLE_SUPER_ADMIN]),
            default => false,
        };
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AssistantConversation::class);
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(AssistantMessage::class, AssistantConversation::class, 'user_id', 'conversation_id');
    }

    public function documentChunks(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentChunk::class, Document::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function assistantDisplayName(): string
    {
        $assistantName = trim((string) $this->assistant_name);

        return $assistantName !== '' ? $assistantName : "{$this->name}'s Assistant";
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
