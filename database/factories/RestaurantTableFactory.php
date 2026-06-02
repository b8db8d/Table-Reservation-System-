<?php

namespace Database\Factories;

use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantTable>
 */
class RestaurantTableFactory extends Factory
{
    private static int $counter = 0;

    public function definition(): array
    {
        return [
            'name' => 'Table '.(++self::$counter),
            'capacity' => fake()->randomElement([2, 4, 6, 8]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(['capacity' => $capacity]);
    }
}
