<?php

use App\Events\ReservationCancelled;
use App\Mail\ReservationCancelled as ReservationCancelledMail;
use App\Mail\StaffCancellationNotification;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('queues a cancellation email to the guest when a reservation is cancelled', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->cancelled()->create();
    ReservationCancelled::dispatch($reservation);

    Mail::assertQueued(ReservationCancelledMail::class);
});

it('sends the guest cancellation email to the guest address', function (): void {
    Mail::fake();

    $reservation = Reservation::factory()->cancelled()->create(['email' => 'guest@example.com']);
    ReservationCancelled::dispatch($reservation);

    Mail::assertQueued(ReservationCancelledMail::class, function (ReservationCancelledMail $mail) {
        return $mail->hasTo('guest@example.com');
    });
});

it('contains the reference number in the guest cancellation email', function (): void {
    $reservation = Reservation::factory()->cancelled()->create();

    $mailable = new ReservationCancelledMail($reservation);

    $mailable->assertSeeInHtml($reservation->reference_number);
});

it('contains the guest name in the guest cancellation email', function (): void {
    $reservation = Reservation::factory()->cancelled()->create(['first_name' => 'Alice']);

    $mailable = new ReservationCancelledMail($reservation);

    $mailable->assertSeeInHtml('Alice');
});

it('queues cancellation notifications to all staff and manager users', function (): void {
    Mail::fake();

    $manager = User::factory()->manager()->create();
    $staff = User::factory()->staff()->create();
    User::factory()->customer()->create();

    $reservation = Reservation::factory()->cancelled()->create();
    ReservationCancelled::dispatch($reservation);

    Mail::assertQueued(StaffCancellationNotification::class, function (StaffCancellationNotification $mail) use ($manager) {
        return $mail->hasTo($manager->email);
    });

    Mail::assertQueued(StaffCancellationNotification::class, function (StaffCancellationNotification $mail) use ($staff) {
        return $mail->hasTo($staff->email);
    });

    Mail::assertQueued(StaffCancellationNotification::class, 2);
});

it('does not send staff cancellation notification to customer users', function (): void {
    Mail::fake();

    User::factory()->customer()->create();

    $reservation = Reservation::factory()->cancelled()->create();
    ReservationCancelled::dispatch($reservation);

    Mail::assertNotQueued(StaffCancellationNotification::class);
});

it('contains the reference number and guest details in the staff cancellation notification', function (): void {
    $reservation = Reservation::factory()->cancelled()->create([
        'first_name' => 'Bob',
        'reference_number' => 'RES-20250101-000099',
    ]);

    $mailable = new StaffCancellationNotification($reservation);

    $mailable->assertSeeInHtml('RES-20250101-000099');
    $mailable->assertSeeInHtml('Bob');
});

it('guest cancellation and staff notifications are queued, not sent synchronously', function (): void {
    Mail::fake();

    User::factory()->staff()->create();

    $reservation = Reservation::factory()->cancelled()->create();
    ReservationCancelled::dispatch($reservation);

    Mail::assertQueued(ReservationCancelledMail::class);
    Mail::assertQueued(StaffCancellationNotification::class);
    Mail::assertNothingSent();
});
