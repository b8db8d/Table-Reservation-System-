<?php

namespace Database\Factories;

use App\Models\JoiningGroupRestriction;
use App\Models\TableJoiningGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JoiningGroupRestriction>
 */
class JoiningGroupRestrictionFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(10, 20);

        return [
            'table_joining_group_id' => TableJoiningGroup::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => sprintf('%02d:00:00', $start),
            'end_time' => sprintf('%02d:00:00', $start + 2),
        ];
    }

    public function everyDay(): static
    {
        return $this->state(['day_of_week' => null]);
    }

    public function forDay(int $dayOfWeek): static
    {
        return $this->state(['day_of_week' => $dayOfWeek]);
    }

    public function betweenHours(int $startHour, int $endHour): static
    {
        return $this->state([
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $endHour),
        ]);
    }
}
