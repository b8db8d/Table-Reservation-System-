<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Events\ReservationCancelled;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationCancellationController extends Controller
{
    public function cancel(Request $request, Reservation $reservation, string $token): RedirectResponse
    {
        if ($reservation->status === ReservationStatus::Cancelled) {
            return redirect()->route('reservations.cancelled')->with('message', 'already_cancelled');
        }

        if ($reservation->cancellation_token !== $token) {
            abort(403);
        }

        if ($reservation->status !== ReservationStatus::Confirmed) {
            abort(403);
        }

        $reservation->restaurantTables()->detach();

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_token' => null,
        ]);

        ReservationCancelled::dispatch($reservation);

        return redirect()->route('reservations.cancelled');
    }
}
