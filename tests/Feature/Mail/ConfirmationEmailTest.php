<?php

use App\Events\ReservationConfirmed;
use App\Mail\ReservationConfirmation;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;

it('queues a confirmation email when a reservation is confirmed', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->confirmed()->create();
    ReservationConfirmed::dispatch($reservation);

    Mail::assertQueued(ReservationConfirmation::class);
});

it('sends the confirmation email to the guest address', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->confirmed()->create(['email' => 'guest@example.com']);
    ReservationConfirmed::dispatch($reservation);

    Mail::assertQueued(ReservationConfirmation::class, function (ReservationConfirmation $mail) {
        return $mail->hasTo('guest@example.com');
    });
});

it('contains a cancellation link in the email body', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    $mailable = new ReservationConfirmation($reservation);

    $mailable->assertSeeInHtml('reservations/'.$reservation->id.'/cancel');
    $mailable->assertSeeInHtml($reservation->cancellation_token);
});

it('includes the admin BCC when configured', function (): void {
    config(['mail.admin_bcc' => 'admin@example.com']);

    $reservation = Reservation::factory()->confirmed()->create();

    $mailable = new ReservationConfirmation($reservation);

    $mailable->assertHasBcc('admin@example.com');
});

it('does not add a BCC when admin email is not configured', function (): void {
    config(['mail.admin_bcc' => null]);

    $reservation = Reservation::factory()->confirmed()->create();

    $mailable = new ReservationConfirmation($reservation);

    expect($mailable->envelope()->bcc)->toBeEmpty();
});

it('contains the reference number in the email body', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    $mailable = new ReservationConfirmation($reservation);

    $mailable->assertSeeInHtml($reservation->reference_number);
});
