<?php

use App\Events\ReservationCreated;
use App\Mail\ReservationAcknowledgement;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;

it('queues an acknowledgement email when a reservation is created', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->create();
    ReservationCreated::dispatch($reservation);

    Mail::assertQueued(ReservationAcknowledgement::class);
});

it('sends the email to the guest address', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->create(['email' => 'guest@example.com']);
    ReservationCreated::dispatch($reservation);

    Mail::assertQueued(ReservationAcknowledgement::class, function (ReservationAcknowledgement $mail) {
        return $mail->hasTo('guest@example.com');
    });
});

it('contains the reference number in the email body', function (): void {
    $reservation = Reservation::factory()->create();

    $mailable = new ReservationAcknowledgement($reservation);

    $mailable->assertSeeInHtml($reservation->reference_number);
});

it('contains the reservation details in the email body', function (): void {
    $reservation = Reservation::factory()->create();

    $mailable = new ReservationAcknowledgement($reservation);

    $mailable->assertSeeInHtml($reservation->first_name);
    $mailable->assertSeeInHtml((string) $reservation->guest_count);
});
