<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $pendingCount = Reservation::where('status', ReservationStatus::Pending)->count();

        $todayReservations = Reservation::where('status', ReservationStatus::Confirmed)
            ->whereDate('reservation_date', today())
            ->orderBy('reservation_time')
            ->get(['id', 'reference_number', 'first_name', 'last_name', 'guest_count', 'reservation_time']);

        $tomorrowReservations = Reservation::where('status', ReservationStatus::Confirmed)
            ->whereDate('reservation_date', today()->addDay())
            ->orderBy('reservation_time')
            ->get(['id', 'reference_number', 'first_name', 'last_name', 'guest_count', 'reservation_time']);

        return inertia('Admin/Dashboard', [
            'pendingCount' => $pendingCount,
            'todayReservations' => $todayReservations,
            'tomorrowReservations' => $tomorrowReservations,
        ]);
    }
}
