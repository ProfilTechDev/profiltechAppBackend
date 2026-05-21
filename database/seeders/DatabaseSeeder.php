<?php

namespace Database\Seeders;

use App\Authorization\Permissions;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $testUser->givePermissionTo([
            Permissions::CUSTOM_ORDERS_VIEW,
            Permissions::CUSTOM_ORDERS_MANAGE,
            Permissions::USERS_MANAGE,
        ]);
    }
}
