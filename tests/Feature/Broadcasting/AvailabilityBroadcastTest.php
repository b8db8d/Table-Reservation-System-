<?php

use App\Events\AvailabilityUpdated;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationConfirmationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('broadcasts AvailabilityUpdated when a reservation is confirmed', function (): void {
    Event::fake([AvailabilityUpdated::class]);

    $reservation = Reservation::factory()->pending()->create();
    $manager = User::factory()->manager()->create();

    app(ReservationConfirmationService::class)->confirm($reservation, $manager->id);

    Event::assertDispatched(AvailabilityUpdated::class);
});

it('broadcasts AvailabilityUpdated when a reservation is cancelled', function (): void {
    Event::fake([AvailabilityUpdated::class]);

    $reservation = Reservation::factory()->confirmed()->create();

    $this->get("/reservations/{$reservation->id}/cancel/{$reservation->cancellation_token}");

    Event::assertDispatched(AvailabilityUpdated::class);
});

it('AvailabilityUpdated event broadcasts on the public availability channel', function (): void {
    $event = new AvailabilityUpdated;
    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(Channel::class)
        ->and($channels[0]->name)->toBe('availability');
});
