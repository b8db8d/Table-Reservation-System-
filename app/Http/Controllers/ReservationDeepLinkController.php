<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\ReservationConfirmationService;
use App\Services\ReservationRejectionService;
use Illuminate\Http\Request;
use Inertia\Response;

class ReservationDeepLinkController extends Controller
{
    public function confirm(Request $request, Reservation $reservation, ReservationConfirmationService $service): Response
    {
        $service->confirm($reservation, confirmedBy: null);

        return inertia('ReservationConfirmed', [
            'referenceNumber' => $reservation->reference_number,
        ]);
    }

    public function rejectForm(Request $request, Reservation $reservation): Response
    {
        return inertia('ReservationRejection', [
            'reservation' => [
                'reference_number' => $reservation->reference_number,
                'first_name' => $reservation->first_name,
                'last_name' => $reservation->last_name,
                'reservation_date' => $reservation->reservation_date->toDateString(),
                'reservation_time' => $reservation->reservation_time,
                'guest_count' => $reservation->guest_count,
            ],
            'submitUrl' => $request->fullUrl(),
        ]);
    }

    public function reject(Request $request, Reservation $reservation, ReservationRejectionService $service): Response
    {
        $request->validate(['rejection_reason' => ['required', 'string']]);

        $service->reject($reservation, $request->string('rejection_reason'), rejectedBy: null);

        return inertia('ReservationRejected', [
            'referenceNumber' => $reservation->reference_number,
        ]);
    }
}
