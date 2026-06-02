<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Events\ReservationConfirmed;
use App\Models\Reservation;
use Illuminate\Validation\ValidationException;

class ReservationConfirmationService
{
    public function confirm(Reservation $reservation, ?int $confirmedBy = null): void
    {
        if ($reservation->status === ReservationStatus::Confirmed) {
            return;
        }

        if ($reservation->status !== ReservationStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending reservations can be confirmed.',
            ]);
        }

        $reservation->update([
            'status' => ReservationStatus::Confirmed,
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
        ]);

        ReservationConfirmed::dispatch($reservation);
    }
}
