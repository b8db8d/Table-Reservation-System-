<?php

use App\Enums\ReservationStatus;
use App\Events\ReservationConfirmed;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([ReservationConfirmed::class]);
});

it('staff can confirm a pending reservation', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->patch("/admin/reservations/{$reservation->id}/confirm")
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Confirmed);
});

it('sets confirmed_by to the acting user', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->patch("/admin/reservations/{$reservation->id}/confirm");

    expect($reservation->fresh()->confirmed_by)->toBe($staff->id);
});

it('sets confirmed_at timestamp', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/confirm");

    expect($reservation->fresh()->confirmed_at)->not->toBeNull();
});

it('dispatches the ReservationConfirmed event', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/confirm");

    Event::assertDispatched(ReservationConfirmed::class, fn ($e) => $e->reservation->is($reservation));
});

it('is idempotent when reservation is already confirmed', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/confirm")
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Confirmed);
    Event::assertNotDispatched(ReservationConfirmed::class);
});

it('cannot confirm a rejected reservation', function (): void {
    $reservation = Reservation::factory()->rejected()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/confirm")
        ->assertSessionHasErrors('status');
});

it('cannot confirm a cancelled reservation', function (): void {
    $reservation = Reservation::factory()->cancelled()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/confirm")
        ->assertSessionHasErrors('status');
});

it('valid deep-link confirms without requiring login', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $url = URL::temporarySignedRoute('reservations.deeplink.confirm', now()->addHours(72), ['reservation' => $reservation]);

    $this->get($url)
        ->assertInertia(fn ($page) => $page->component('ReservationConfirmed'));

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Confirmed);
    Event::assertDispatched(ReservationConfirmed::class);
});

it('unsigned deep-link returns 403', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->get("/reservations/{$reservation->id}/confirm")
        ->assertForbidden();
});
