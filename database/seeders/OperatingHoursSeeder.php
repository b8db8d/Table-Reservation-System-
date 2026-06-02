<?php

namespace Database\Seeders;

use App\Models\OperatingHours;
use Illuminate\Database\Seeder;

class OperatingHoursSeeder extends Seeder
{
    public function run(): void
    {
        $hours = [
            ['day_of_week' => 0, 'open_time' => '12:00:00', 'close_time' => '03:00:00', 'is_closed' => false], // Sunday
            ['day_of_week' => 1, 'open_time' => '12:00:00', 'close_time' => '22:00:00', 'is_closed' => true], // Monday
            ['day_of_week' => 2, 'open_time' => '16:00:00', 'close_time' => '20:00:00', 'is_closed' => true], // Tuesday
            ['day_of_week' => 3, 'open_time' => '12:00:00', 'close_time' => '20:00:00', 'is_closed' => false], // Wednesday
            ['day_of_week' => 4, 'open_time' => '12:00:00', 'close_time' => '23:00:00', 'is_closed' => false], // Thursday
            ['day_of_week' => 5, 'open_time' => '16:00:00', 'close_time' => '02:00:00', 'is_closed' => false], // Friday
            ['day_of_week' => 6, 'open_time' => '12:00:00', 'close_time' => '02:00:00', 'is_closed' => false], // Saturday
        ];

        foreach ($hours as $row) {
            OperatingHours::create($row);
        }
    }
}
