<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('now', '+3 months');

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'guest_count' => fake()->numberBetween(1, 8),
            'reservation_date' => $date->format('Y-m-d'),
            'reservation_time' => fake()->time('H:i:s', '22:00:00'),
            'status' => ReservationStatus::Pending,
            'notes' => null,
            'rejection_reason' => null,
            'user_id' => null,
            'confirmed_by' => null,
            'confirmed_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
            'cancellation_token' => Str::uuid()->toString(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ReservationStatus::Pending]);
    }

    public function confirmed(): static
    {
        return $this->state(function () {
            $user = User::factory()->create();

            return [
                'status' => ReservationStatus::Confirmed,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'cancellation_token' => Str::uuid()->toString(),
            ];
        });
    }

    public function rejected(): static
    {
        return $this->state(function () {
            $user = User::factory()->create();

            return [
                'status' => ReservationStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => fake()->sentence(),
                'cancellation_token' => null,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_token' => null,
        ]);
    }
}
