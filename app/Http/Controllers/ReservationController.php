<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Events\ReservationCreated;
use App\Exceptions\NoTableAvailableException;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use App\Services\TableAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly TableAssignmentService $tableAssignmentService,
    ) {}

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $date = Carbon::parse($data['reservation_date']);
        $time = Carbon::parse($data['reservation_time']);
        $guestCount = (int) $data['guest_count'];

        $available = $this->availabilityService->getAvailableCapacity($date, $time);

        $hasSufficientTable = $available['tables']->contains(fn ($t) => $t->capacity >= $guestCount);
        $hasSufficientGroup = $available['groups']->contains(
            fn ($g) => $g->restaurantTables->sum('capacity') >= $guestCount && $guestCount >= $g->min_guests
        );

        if (! $hasSufficientTable && ! $hasSufficientGroup) {
            return back()->withErrors([
                'guest_count' => 'No tables are available for the requested slot.',
            ])->withInput();
        }

        try {
            $reservation = Reservation::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'guest_count' => $guestCount,
                'reservation_date' => $data['reservation_date'],
                'reservation_time' => $data['reservation_time'].':00',
                'notes' => $data['notes'] ?? null,
                'status' => ReservationStatus::Pending,
                'cancellation_token' => Str::uuid()->toString(),
                'user_id' => auth()->id(),
            ]);

            $this->tableAssignmentService->assignTables($reservation);
        } catch (NoTableAvailableException) {
            return back()->withErrors([
                'guest_count' => 'No tables are available for the requested slot.',
            ])->withInput();
        }

        ReservationCreated::dispatch($reservation);

        return redirect()->route('reservations.success')
            ->with('reference_number', $reservation->reference_number);
    }
}
