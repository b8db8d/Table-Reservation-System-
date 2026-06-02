<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = CarbonImmutable::today();
        $email = $request->user()->email;

        $reservations = Reservation::where('email', $email)
            ->orderByDesc('reservation_date')
            ->orderByDesc('reservation_time')
            ->get()
            ->map(fn (Reservation $r) => [
                'id' => $r->id,
                'reference_number' => $r->reference_number,
                'reservation_date' => $r->reservation_date,
                'reservation_time' => substr($r->reservation_time, 0, 5),
                'guest_count' => $r->guest_count,
                'status' => $r->status->value,
                'cancel_url' => $this->cancelUrl($r, $today),
            ]);

        return Inertia::render('Dashboard', ['reservations' => $reservations]);
    }

    private function cancelUrl(Reservation $reservation, CarbonImmutable $today): ?string
    {
        if ($reservation->status !== ReservationStatus::Confirmed) {
            return null;
        }

        if ($reservation->reservation_date < $today->toDateString()) {
            return null;
        }

        if (! $reservation->cancellation_token) {
            return null;
        }

        return route('reservations.cancel', [
            'reservation' => $reservation->id,
            'token' => $reservation->cancellation_token,
        ]);
    }
}
