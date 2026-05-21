<?php

namespace App\Authorization;

/**
 * Canonical list of permission keys used across the app.
 *
 * Each permission represents an "area" the admin can check off when
 * creating or editing a user. Permissions are persisted via
 * spatie/laravel-permission; this class is the single source of truth
 * for what those permission names are, so controllers, policies, the
 * seeder, and the frontend all agree on the strings.
 *
 * Adding a new permission:
 *   1. Add a constant + label here.
 *   2. Run `php artisan db:seed --class=PermissionSeeder` to insert it.
 *   3. Surface it in the frontend permissions config so the checkbox
 *      shows up in the user dialogs.
 */
class Permissions
{
    public const CUSTOM_ORDERS_VIEW = 'custom-orders.view';

    public const CUSTOM_ORDERS_MANAGE = 'custom-orders.manage';

    public const USERS_MANAGE = 'users.manage';

    public const WAREHOUSE_ACCESS = 'warehouse.access';

    /**
     * Permission key => human-readable Danish label (used as a fallback
     * for places that need a label without consulting the frontend
     * config; the source-of-truth for UI labels lives on the frontend).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            self::CUSTOM_ORDERS_VIEW => 'Se bestillingsordrer',
            self::CUSTOM_ORDERS_MANAGE => 'Håndtér bestillingsordrer',
            self::USERS_MANAGE => 'Håndtér brugere',
            self::WAREHOUSE_ACCESS => 'Adgang til lager',
        ];
    }

    /**
     * Flat list of permission keys, for validation rules.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }
}
