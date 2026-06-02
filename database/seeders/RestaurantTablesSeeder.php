<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class RestaurantTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['name' => 'Table-2a', 'capacity' => 2, 'is_active' => true],
            ['name' => 'Table-2b', 'capacity' => 2, 'is_active' => true],
            ['name' => 'Table-5', 'capacity' => 5, 'is_active' => true],
            ['name' => 'Table-6', 'capacity' => 6, 'is_active' => true],
            ['name' => 'Table-8', 'capacity' => 8, 'is_active' => true],

        ];

        foreach ($tables as $row) {
            RestaurantTable::create($row);
        }
    }
}
