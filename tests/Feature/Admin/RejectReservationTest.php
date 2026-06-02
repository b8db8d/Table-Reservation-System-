<?php

use App\Enums\ReservationStatus;
use App\Events\ReservationRejected;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Event::fake([ReservationRejected::class]);
});

it('staff can reject a pending reservation with a reason', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/reject", [
            'rejection_reason' => 'No availability for the requested time.',
        ])
        ->assertRedirect();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Rejected);
});

it('rejection_reason is required', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/reject")
        ->assertSessionHasErrors('rejection_reason');
});

it('status changes to rejected', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/reject", [
            'rejection_reason' => 'Fully booked.',
        ]);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Rejected)
        ->and($reservation->fresh()->rejection_reason)->toBe('Fully booked.')
        ->and($reservation->fresh()->rejected_by)->not->toBeNull()
        ->and($reservation->fresh()->rejected_at)->not->toBeNull();
});

it('dispatches the ReservationRejected event', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/reject", [
            'rejection_reason' => 'No tables available.',
        ]);

    Event::assertDispatched(ReservationRejected::class, fn ($e) => $e->reservation->is($reservation));
});

it('cannot reject an already-confirmed reservation', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->patch("/admin/reservations/{$reservation->id}/reject", [
            'rejection_reason' => 'A reason.',
        ])
        ->assertSessionHasErrors('status');
});

it('deep-link without login loads the rejection form', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $url = URL::temporarySignedRoute('reservations.deeplink.reject', now()->addHours(72), ['reservation' => $reservation]);

    $this->get($url)
        ->assertInertia(fn ($page) => $page
            ->component('ReservationRejection')
            ->where('reservation.reference_number', $reservation->reference_number)
        );
});

it('deep-link rejects reservation when form submitted with reason', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $url = URL::temporarySignedRoute('reservations.deeplink.reject', now()->addHours(72), ['reservation' => $reservation]);

    $this->post($url, ['rejection_reason' => 'No capacity.'])
        ->assertInertia(fn ($page) => $page->component('ReservationRejected'));

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Rejected);
    Event::assertDispatched(ReservationRejected::class);
});

it('unsigned deep-link returns 403', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->get("/reservations/{$reservation->id}/reject")
        ->assertForbidden();
});
