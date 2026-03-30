<?php

use App\Models\LandingPageContent;
use App\Models\User;
use App\Support\LandingPageDefaults;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate(User::ROLE_CUSTOMER, 'web');
        Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');

        User::query()
            ->whereKey(1)
            ->first()?->syncRoles([User::ROLE_SUPER_ADMIN]);

        User::query()
            ->where('id', '!=', 1)
            ->get()
            ->each(function (User $user): void {
                if ($user->roles()->doesntExist()) {
                    $user->assignRole(User::ROLE_CUSTOMER);
                }
            });

        LandingPageContent::query()->firstOrCreate(
            ['slug' => 'home'],
            LandingPageDefaults::content(),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        LandingPageContent::query()->where('slug', 'home')->delete();

        User::query()->get()->each(fn (User $user) => $user->roles()->detach());

        Role::query()->whereIn('name', [User::ROLE_CUSTOMER, User::ROLE_SUPER_ADMIN])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
