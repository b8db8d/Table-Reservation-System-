<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Events\ReservationRejected;
use App\Models\Reservation;
use Illuminate\Validation\ValidationException;

class ReservationRejectionService
{
    public function reject(Reservation $reservation, string $reason, ?int $rejectedBy = null): void
    {
        if ($reservation->status !== ReservationStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending reservations can be rejected.',
            ]);
        }

        $reservation->update([
            'status' => ReservationStatus::Rejected,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        ReservationRejected::dispatch($reservation);
    }
}
