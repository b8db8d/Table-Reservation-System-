<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(OperatingHoursSeeder::class);
        $this->call(RestaurantTablesSeeder::class);

        User::factory()->create([
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('managerpass'),
        ])->assignRole(Role::Manager);

        User::factory()->create([
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('staffpass'),
        ])->assignRole(Role::Staff);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('customerpass'),
        ])->assignRole(Role::Customer);
    }
}
