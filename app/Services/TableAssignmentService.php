<?php

namespace App\Services;

use App\Exceptions\NoTableAvailableException;
use App\Models\Reservation;
use Carbon\Carbon;

class TableAssignmentService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @throws NoTableAvailableException
     */
    public function assignTables(Reservation $reservation): void
    {
        $date = $reservation->reservation_date; // CarbonImmutable from 'date' cast
        $time = Carbon::parse($reservation->reservation_time);

        $available = $this->availability->getAvailableCapacity($date, $time);

        $guestCount = $reservation->guest_count;

        // Pick the smallest individual table that fits
        $table = $available['tables']
            ->where('capacity', '>=', $guestCount)
            ->sortBy('capacity')
            ->first();

        if ($table !== null) {
            $reservation->restaurantTables()->attach($table->id);

            return;
        }

        // Fall back to a joining group
        $group = $available['groups']
            ->filter(fn ($g) => $g->min_guests <= $guestCount && $g->combinedCapacity() >= $guestCount)
            ->first();

        if ($group !== null) {
            $reservation->restaurantTables()->attach($group->restaurantTables->pluck('id'));

            return;
        }

        throw new NoTableAvailableException('No table available for the requested slot.');
    }
}
