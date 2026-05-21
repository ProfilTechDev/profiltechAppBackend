<?php

use App\Authorization\Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotently sync the `permissions` table with the canonical list in
 * `App\Authorization\Permissions`. New permissions added to that class
 * after the original backfill (e.g. `warehouse.access`) wouldn't reach
 * prod otherwise because `PermissionSeeder` isn't part of the deploy
 * pipeline.
 *
 * Removed permissions are NOT pruned here; clean those up by hand if
 * needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = (string) config('auth.defaults.guard', 'web');

        foreach (Permissions::keys() as $name) {
            Permission::findOrCreate($name, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No-op — rolling back this migration should not delete
        // permissions, since other migrations and runtime data depend
        // on their existence.
    }
};
