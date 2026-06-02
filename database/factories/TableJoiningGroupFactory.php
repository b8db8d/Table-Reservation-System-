<?php

namespace Database\Factories;

use App\Models\TableJoiningGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TableJoiningGroup>
 */
class TableJoiningGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => null,
            'min_guests' => fake()->numberBetween(5, 8),
        ];
    }

    public function named(string $name): static
    {
        return $this->state(['name' => $name]);
    }

    public function withMinGuests(int $minGuests): static
    {
        return $this->state(['min_guests' => $minGuests]);
    }
}
