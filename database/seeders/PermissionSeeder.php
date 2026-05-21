<?php

namespace Database\Seeders;

use App\Authorization\Permissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Insert the application's known permissions into the database. Idempotent —
 * safe to run after adding a new constant to App\Authorization\Permissions.
 * Removed permissions are NOT pruned automatically; clean those up by hand.
 */
class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (Permissions::keys() as $name) {
            Permission::findOrCreate($name, config('auth.defaults.guard', 'web'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
