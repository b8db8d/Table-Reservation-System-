<?php

namespace Database\Factories;

use App\Models\OperatingHours;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperatingHours>
 */
class OperatingHoursFactory extends Factory
{
    public function definition(): array
    {
        $open = fake()->numberBetween(10, 14);

        return [
            'day_of_week' => fake()->unique()->numberBetween(0, 6),
            'open_time' => sprintf('%02d:00:00', $open),
            'close_time' => sprintf('%02d:00:00', $open + 8),
            'is_closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state([
            'open_time' => null,
            'close_time' => null,
            'is_closed' => true,
        ]);
    }

    public function forDay(int $dayOfWeek): static
    {
        return $this->state(['day_of_week' => $dayOfWeek]);
    }

    public function openBetween(string $openTime, string $closeTime): static
    {
        return $this->state([
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'is_closed' => false,
        ]);
    }
}
