<?php

use App\Enums\ReservationStatus;
use App\Events\ReservationConfirmed;
use App\Events\ReservationCreated;
use App\Models\OperatingHours;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([ReservationCreated::class, ReservationConfirmed::class]);

    foreach (range(1, 5) as $day) {
        OperatingHours::factory()->create([
            'day_of_week' => $day,
            'open_time' => '12:00:00',
            'close_time' => '22:00:00',
            'is_closed' => false,
        ]);
    }
    foreach ([0, 6] as $day) {
        OperatingHours::factory()->create([
            'day_of_week' => $day,
            'open_time' => null,
            'close_time' => null,
            'is_closed' => true,
        ]);
    }

    RestaurantTable::factory()->create(['capacity' => 4]);

    $this->date = Carbon::parse('next Monday')->toDateString();
    $this->time = '18:00';

    $this->validPayload = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'phone' => '+48123456789',
        'guest_count' => 2,
        'reservation_date' => $this->date,
        'reservation_time' => $this->time,
        'status' => 'pending',
    ];
});

it('renders the create form for staff', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/reservations/create')
        ->assertInertia(fn ($page) => $page->component('Admin/Reservations/Create'));
});

it('staff can create a pending reservation', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->post('/admin/reservations', $this->validPayload)
        ->assertRedirectToRoute('admin.reservations.index');

    $reservation = Reservation::first();
    expect($reservation->status)->toBe(ReservationStatus::Pending)
        ->and($reservation->first_name)->toBe('Jane');
});

it('staff can create a confirmed reservation directly', function (): void {
    $payload = array_merge($this->validPayload, ['status' => 'confirmed']);

    $this->actingAs(User::factory()->staff()->create())
        ->post('/admin/reservations', $payload)
        ->assertRedirectToRoute('admin.reservations.index');

    $reservation = Reservation::first();
    expect($reservation->status)->toBe(ReservationStatus::Confirmed)
        ->and($reservation->confirmed_by)->not->toBeNull()
        ->and($reservation->confirmed_at)->not->toBeNull();
});

it('table availability is validated', function (): void {
    // Fill the table with a confirmed reservation for the same slot
    Reservation::factory()->confirmed()->create([
        'reservation_date' => $this->date,
        'reservation_time' => $this->time.':00',
        'guest_count' => 4,
    ]);
    RestaurantTable::first()->reservations()->attach(
        Reservation::first()->id
    );

    $this->actingAs(User::factory()->staff()->create())
        ->post('/admin/reservations', $this->validPayload)
        ->assertSessionHasErrors('guest_count');
});

it('dispatches ReservationConfirmed when created as confirmed', function (): void {
    $payload = array_merge($this->validPayload, ['status' => 'confirmed']);

    $this->actingAs(User::factory()->staff()->create())
        ->post('/admin/reservations', $payload);

    Event::assertDispatched(ReservationConfirmed::class);
    Event::assertNotDispatched(ReservationCreated::class);
});

it('dispatches ReservationCreated when created as pending', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->post('/admin/reservations', $this->validPayload);

    Event::assertDispatched(ReservationCreated::class);
    Event::assertNotDispatched(ReservationConfirmed::class);
});
