<?php

use App\Authorization\Permissions;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Grant the three default permissions (custom-orders.view + .manage and
 * users.manage) to every user that existed before the permission system
 * was rolled out. New users created via the invitation flow start
 * empty — only the legacy set gets backfilled here.
 *
 * Idempotent: re-runs are safe because `givePermissionTo` no-ops when
 * the user already has the permission. Permissions are findOrCreate'd
 * so the migration works even if PermissionSeeder hasn't been invoked
 * yet on the target environment.
 */
return new class extends Migration
{
    private const DEFAULT_PERMISSIONS = [
        Permissions::CUSTOM_ORDERS_VIEW,
        Permissions::CUSTOM_ORDERS_MANAGE,
        Permissions::USERS_MANAGE,
    ];

    public function up(): void
    {
        $guard = (string) config('auth.defaults.guard', 'web');

        foreach (self::DEFAULT_PERMISSIONS as $name) {
            Permission::findOrCreate($name, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::query()->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $user->givePermissionTo(self::DEFAULT_PERMISSIONS);
            }
        });
    }

    public function down(): void
    {
        User::query()->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $user->revokePermissionTo(self::DEFAULT_PERMISSIONS);
            }
        });
    }
};
