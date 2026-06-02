<?php

use App\Events\ReservationRejected;
use App\Mail\ReservationRejection;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;

it('queues a rejection email when a reservation is rejected', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->rejected()->create();
    ReservationRejected::dispatch($reservation);

    Mail::assertQueued(ReservationRejection::class);
});

it('sends the rejection email to the guest address', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->rejected()->create(['email' => 'guest@example.com']);
    ReservationRejected::dispatch($reservation);

    Mail::assertQueued(ReservationRejection::class, function (ReservationRejection $mail) {
        return $mail->hasTo('guest@example.com');
    });
});

it('contains the rejection reason in the email body', function (): void {
    $reservation = Reservation::factory()->rejected()->create([
        'rejection_reason' => 'We are fully booked on that date.',
    ]);

    $mailable = new ReservationRejection($reservation);

    $mailable->assertSeeInHtml('We are fully booked on that date.');
});

it('contains the reservation reference number in the email body', function (): void {
    $reservation = Reservation::factory()->rejected()->create();

    $mailable = new ReservationRejection($reservation);

    $mailable->assertSeeInHtml($reservation->reference_number);
});

it('contains the guest name in the email body', function (): void {
    $reservation = Reservation::factory()->rejected()->create(['first_name' => 'Alice']);

    $mailable = new ReservationRejection($reservation);

    $mailable->assertSeeInHtml('Alice');
});
