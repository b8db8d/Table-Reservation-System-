<?php

use App\Enums\ReservationStatus;
use App\Events\ReservationConfirmed;
use App\Models\OperatingHours;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([ReservationConfirmed::class]);

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

    $this->table = RestaurantTable::factory()->create(['capacity' => 4]);
    $this->date = Carbon::parse('next Monday')->toDateString();
    $this->nextDate = Carbon::parse('next Tuesday')->toDateString();
});

it('renders the edit form for staff', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->get("/admin/reservations/{$reservation->id}/edit")
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Reservations/Edit')
            ->where('reservation.id', $reservation->id)
        );
});

it('staff can update guest details', function (): void {
    $reservation = Reservation::factory()->pending()->create([
        'reservation_date' => $this->date,
        'reservation_time' => '18:00:00',
    ]);
    $reservation->restaurantTables()->attach($this->table->id);

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => 'updated@example.com',
            'phone' => '+48123456789',
            'guest_count' => $reservation->guest_count,
            'reservation_date' => $this->date,
            'reservation_time' => '18:00',
            'status' => 'pending',
            'notes' => null,
        ])
        ->assertRedirectToRoute('admin.reservations.index');

    expect($reservation->fresh()->first_name)->toBe('Updated')
        ->and($reservation->fresh()->email)->toBe('updated@example.com');
});

it('changing date re-validates availability', function (): void {
    $reservation = Reservation::factory()->pending()->create([
        'reservation_date' => $this->date,
        'reservation_time' => '18:00:00',
        'guest_count' => 2,
    ]);

    // Block the next date by assigning the only table to another confirmed reservation
    $blocking = Reservation::factory()->confirmed()->create([
        'reservation_date' => $this->nextDate,
        'reservation_time' => '18:00:00',
        'guest_count' => 4,
    ]);
    $blocking->restaurantTables()->attach($this->table->id);

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}", [
            'first_name' => $reservation->first_name,
            'last_name' => $reservation->last_name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'guest_count' => 2,
            'reservation_date' => $this->nextDate,
            'reservation_time' => '18:00',
            'status' => 'pending',
            'notes' => null,
        ])
        ->assertSessionHasErrors('guest_count');
});

it('changing date reassigns tables', function (): void {
    $reservation = Reservation::factory()->pending()->create([
        'reservation_date' => $this->date,
        'reservation_time' => '18:00:00',
        'guest_count' => 2,
    ]);
    $reservation->restaurantTables()->attach($this->table->id);

    $newTable = RestaurantTable::factory()->create(['capacity' => 4]);

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}", [
            'first_name' => $reservation->first_name,
            'last_name' => $reservation->last_name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'guest_count' => 2,
            'reservation_date' => $this->nextDate,
            'reservation_time' => '18:00',
            'status' => 'pending',
            'notes' => null,
        ]);

    expect($reservation->fresh()->restaurantTables()->count())->toBe(1)
        ->and($reservation->fresh()->reservation_date->toDateString())->toBe($this->nextDate);
});

it('changing status to confirmed dispatches ReservationConfirmed event', function (): void {
    $reservation = Reservation::factory()->pending()->create([
        'reservation_date' => $this->date,
        'reservation_time' => '18:00:00',
        'guest_count' => 2,
    ]);
    $reservation->restaurantTables()->attach($this->table->id);

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}", [
            'first_name' => $reservation->first_name,
            'last_name' => $reservation->last_name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'guest_count' => $reservation->guest_count,
            'reservation_date' => $this->date,
            'reservation_time' => '18:00',
            'status' => 'confirmed',
            'notes' => null,
        ]);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Confirmed)
        ->and($reservation->fresh()->confirmed_by)->not->toBeNull();

    Event::assertDispatched(ReservationConfirmed::class);
});

it('manager can edit a reservation', function (): void {
    $reservation = Reservation::factory()->pending()->create([
        'reservation_date' => $this->date,
        'reservation_time' => '18:00:00',
    ]);
    $reservation->restaurantTables()->attach($this->table->id);

    $this->actingAs(User::factory()->manager()->create())
        ->patch("/admin/reservations/{$reservation->id}", [
            'first_name' => 'Manager',
            'last_name' => 'Edit',
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'guest_count' => $reservation->guest_count,
            'reservation_date' => $this->date,
            'reservation_time' => '18:00',
            'status' => 'pending',
            'notes' => null,
        ])
        ->assertRedirectToRoute('admin.reservations.index');

    expect($reservation->fresh()->first_name)->toBe('Manager');
});

it('customer cannot edit a reservation', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->customer()->create())
        ->patch("/admin/reservations/{$reservation->id}", [
            'first_name' => 'Hacker',
            'last_name' => 'X',
            'email' => 'x@example.com',
            'phone' => '+48123456789',
            'guest_count' => 1,
            'reservation_date' => $this->date,
            'reservation_time' => '18:00',
            'status' => 'pending',
        ])
        ->assertForbidden();
});
