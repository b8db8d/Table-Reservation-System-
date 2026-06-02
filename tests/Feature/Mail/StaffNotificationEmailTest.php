<?php

use App\Events\ReservationCreated;
use App\Mail\StaffReservationNotification;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('queues notification emails to all staff and manager users on new reservation', function (): void {
    Mail::fake();

    $manager = User::factory()->manager()->create();
    $staff = User::factory()->staff()->create();
    User::factory()->customer()->create();

    $reservation = Reservation::factory()->create();
    ReservationCreated::dispatch($reservation);

    Mail::assertQueued(StaffReservationNotification::class, function (StaffReservationNotification $mail) use ($manager) {
        return $mail->hasTo($manager->email);
    });

    Mail::assertQueued(StaffReservationNotification::class, function (StaffReservationNotification $mail) use ($staff) {
        return $mail->hasTo($staff->email);
    });

    Mail::assertQueued(StaffReservationNotification::class, 2);
});

it('does not send notification to customer users', function (): void {
    Mail::fake();

    User::factory()->customer()->create();

    $reservation = Reservation::factory()->create();
    ReservationCreated::dispatch($reservation);

    Mail::assertNotQueued(StaffReservationNotification::class);
});

it('contains a valid signed confirm URL', function (): void {
    $reservation = Reservation::factory()->create();

    $mailable = new StaffReservationNotification($reservation);

    $mailable->assertSeeInHtml('reservations/'.$reservation->id.'/confirm');
    $mailable->assertSeeInHtml('signature=');
});

it('contains a valid signed reject URL', function (): void {
    $reservation = Reservation::factory()->create();

    $mailable = new StaffReservationNotification($reservation);

    $mailable->assertSeeInHtml('reservations/'.$reservation->id.'/reject');
    $mailable->assertSeeInHtml('signature=');
});

it('generates different deep-link URLs for different reservations', function (): void {
    $first = Reservation::factory()->create();
    $second = Reservation::factory()->create();

    $firstMail = new StaffReservationNotification($first);
    $secondMail = new StaffReservationNotification($second);

    expect($firstMail->confirmUrl)->not->toBe($secondMail->confirmUrl)
        ->and($firstMail->rejectUrl)->not->toBe($secondMail->rejectUrl);
});

it('contains the reference number and guest details', function (): void {
    $reservation = Reservation::factory()->create([
        'first_name' => 'Alice',
        'reference_number' => 'RES-20250101-000001',
    ]);

    $mailable = new StaffReservationNotification($reservation);

    $mailable->assertSeeInHtml('RES-20250101-000001');
    $mailable->assertSeeInHtml('Alice');
});
