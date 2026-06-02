<?php

use App\Enums\ReservationStatus;
use App\Events\ReservationCancelled;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([ReservationCancelled::class]);
});

it('cancels a confirmed reservation with a valid token', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();
    $token = $reservation->cancellation_token;

    $this->get(route('reservations.cancel', [$reservation, $token]))
        ->assertRedirectToRoute('reservations.cancelled');

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::Cancelled)
        ->and($reservation->cancelled_at)->not->toBeNull()
        ->and($reservation->cancellation_token)->toBeNull();
});

it('dispatches the ReservationCancelled event on success', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    $this->get(route('reservations.cancel', [$reservation, $reservation->cancellation_token]));

    Event::assertDispatched(ReservationCancelled::class, function ($event) use ($reservation) {
        return $event->reservation->id === $reservation->id;
    });
});

it('detaches tables on successful cancellation', function (): void {
    $table = RestaurantTable::factory()->create(['capacity' => 4]);
    $reservation = Reservation::factory()->confirmed()->create();
    $reservation->restaurantTables()->attach($table);

    $this->get(route('reservations.cancel', [$reservation, $reservation->cancellation_token]));

    expect($reservation->restaurantTables()->count())->toBe(0);
});

it('returns 403 for an invalid token', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    $this->get(route('reservations.cancel', [$reservation, 'wrong-token']))
        ->assertStatus(403);
});

it('redirects with already_cancelled message when reservation is already cancelled', function (): void {
    $reservation = Reservation::factory()->cancelled()->create();

    $this->get(route('reservations.cancel', [$reservation, 'any-token']))
        ->assertRedirectToRoute('reservations.cancelled')
        ->assertSessionHas('message', 'already_cancelled');
});

it('returns 403 for a pending reservation', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $token = $reservation->cancellation_token;

    $this->get(route('reservations.cancel', [$reservation, $token]))
        ->assertStatus(403);
});

it('returns 403 for a rejected reservation', function (): void {
    $token = 'some-token';
    $reservation = Reservation::factory()->rejected()->create([
        'cancellation_token' => $token,
    ]);

    $this->get(route('reservations.cancel', [$reservation, $token]))
        ->assertStatus(403);
});
