<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Events\ReservationConfirmed;
use App\Events\ReservationCreated;
use App\Exceptions\NoTableAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminStoreReservationRequest;
use App\Http\Requests\Admin\AdminUpdateReservationRequest;
use App\Models\Reservation;
use App\Services\AvailabilityService;
use App\Services\ReservationConfirmationService;
use App\Services\ReservationRejectionService;
use App\Services\TableAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly TableAssignmentService $tableAssignmentService,
    ) {}

    public function create(): Response
    {
        return inertia('Admin/Reservations/Create', [
            'statuses' => [ReservationStatus::Pending->value, ReservationStatus::Confirmed->value],
        ]);
    }

    public function store(AdminStoreReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $date = Carbon::parse($data['reservation_date']);
        $time = Carbon::parse($data['reservation_time']);
        $guestCount = (int) $data['guest_count'];
        $status = ReservationStatus::from($data['status'] ?? ReservationStatus::Pending->value);

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
                'status' => $status,
                'cancellation_token' => Str::uuid()->toString(),
            ]);

            $this->tableAssignmentService->assignTables($reservation);
        } catch (NoTableAvailableException) {
            return back()->withErrors([
                'guest_count' => 'No tables are available for the requested slot.',
            ])->withInput();
        }

        if ($status === ReservationStatus::Confirmed) {
            $reservation->update([
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
            ]);
            ReservationConfirmed::dispatch($reservation);
        } else {
            ReservationCreated::dispatch($reservation);
        }

        return redirect()->route('admin.reservations.index')
            ->with('success', "Reservation {$reservation->reference_number} created.");
    }

    public function edit(Reservation $reservation): Response
    {
        return inertia('Admin/Reservations/Edit', [
            'reservation' => [
                'id' => $reservation->id,
                'reference_number' => $reservation->reference_number,
                'first_name' => $reservation->first_name,
                'last_name' => $reservation->last_name,
                'email' => $reservation->email,
                'phone' => $reservation->phone,
                'guest_count' => $reservation->guest_count,
                'reservation_date' => $reservation->reservation_date->toDateString(),
                'reservation_time' => substr($reservation->reservation_time, 0, 5),
                'notes' => $reservation->notes,
                'status' => $reservation->status->value,
            ],
            'statuses' => array_column(ReservationStatus::cases(), 'value'),
        ]);
    }

    public function update(AdminUpdateReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validated();
        $newStatus = ReservationStatus::from($data['status']);
        $newGuestCount = (int) $data['guest_count'];

        $slotChanged = $reservation->reservation_date->toDateString() !== $data['reservation_date']
            || substr($reservation->reservation_time, 0, 5) !== $data['reservation_time']
            || $reservation->guest_count !== $newGuestCount;

        if ($slotChanged) {
            $date = Carbon::parse($data['reservation_date']);
            $time = Carbon::parse($data['reservation_time']);

            $reservation->restaurantTables()->detach();

            $available = $this->availabilityService->getAvailableCapacity($date, $time);

            $hasSufficientTable = $available['tables']->contains(fn ($t) => $t->capacity >= $newGuestCount);
            $hasSufficientGroup = $available['groups']->contains(
                fn ($g) => $g->restaurantTables->sum('capacity') >= $newGuestCount && $newGuestCount >= $g->min_guests
            );

            if (! $hasSufficientTable && ! $hasSufficientGroup) {
                return back()->withErrors([
                    'guest_count' => 'No tables are available for the requested slot.',
                ])->withInput();
            }
        }

        $oldStatus = $reservation->status;

        $reservation->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'guest_count' => $newGuestCount,
            'reservation_date' => $data['reservation_date'],
            'reservation_time' => $data['reservation_time'].':00',
            'notes' => $data['notes'] ?? null,
            'status' => $newStatus,
        ]);

        if ($slotChanged) {
            try {
                $this->tableAssignmentService->assignTables($reservation);
            } catch (NoTableAvailableException) {
                return back()->withErrors([
                    'guest_count' => 'No tables are available for the requested slot.',
                ])->withInput();
            }
        }

        if ($oldStatus !== ReservationStatus::Confirmed && $newStatus === ReservationStatus::Confirmed) {
            $reservation->update([
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
            ]);
            ReservationConfirmed::dispatch($reservation->fresh());
        }

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Reservation updated.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->restaurantTables()->detach();
        $reservation->delete();

        return redirect()->route('admin.reservations.index')
            ->with('success', 'Reservation deleted.');
    }

    public function index(): Response
    {
        $reservations = QueryBuilder::for(Reservation::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, string $value): void {
                    $query->where(function ($q) use ($value): void {
                        $q->where('first_name', 'like', "%{$value}%")
                            ->orWhere('last_name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('reference_number', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('date_from', function ($query, string $value): void {
                    $query->whereDate('reservation_date', '>=', $value);
                }),
                AllowedFilter::callback('date_to', function ($query, string $value): void {
                    $query->whereDate('reservation_date', '<=', $value);
                }),
            )
            ->allowedSorts('reservation_date', 'created_at', 'guest_count')
            ->defaultSort('-created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Reservation $r) => [
                'id' => $r->id,
                'reference_number' => $r->reference_number,
                'first_name' => $r->first_name,
                'last_name' => $r->last_name,
                'email' => $r->email,
                'phone' => $r->phone,
                'guest_count' => $r->guest_count,
                'reservation_date' => $r->reservation_date->toDateString(),
                'reservation_time' => $r->reservation_time,
                'status' => $r->status->value,
                'created_at' => $r->created_at->toDateString(),
            ]);

        return inertia('Admin/Reservations/Index', [
            'reservations' => $reservations,
            'filters' => request()->query('filter', []),
            'sort' => request()->query('sort', '-created_at'),
            'statuses' => array_column(ReservationStatus::cases(), 'value'),
            'canDelete' => auth()->user()->can('reservations.delete'),
        ]);
    }

    public function confirm(Reservation $reservation, ReservationConfirmationService $service): RedirectResponse
    {
        $service->confirm($reservation, auth()->id());

        return back()->with('success', 'Reservation confirmed.');
    }

    public function reject(Request $request, Reservation $reservation, ReservationRejectionService $service): RedirectResponse
    {
        $request->validate(['rejection_reason' => ['required', 'string']]);

        $service->reject($reservation, $request->string('rejection_reason'), auth()->id());

        return back()->with('success', 'Reservation rejected.');
    }

    public function pending(): Response
    {
        $reservations = Reservation::where('status', ReservationStatus::Pending)
            ->orderBy('created_at')
            ->get([
                'id',
                'reference_number',
                'first_name',
                'last_name',
                'email',
                'phone',
                'guest_count',
                'reservation_date',
                'reservation_time',
                'notes',
                'created_at',
            ]);

        return inertia('Admin/Reservations/Pending', [
            'reservations' => $reservations,
        ]);
    }
}
