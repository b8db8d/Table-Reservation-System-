<?php

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Str;

it('can be created in pending status', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    expect($reservation->status)->toBe(ReservationStatus::Pending);
});

it('status is cast to ReservationStatus enum', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->status)->toBeInstanceOf(ReservationStatus::class);
});

it('cancellation_token is a valid UUID', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    expect(Str::isUuid($reservation->cancellation_token))->toBeTrue();
});

it('confirmed_at is set when status is confirmed', function (): void {
    $reservation = Reservation::factory()->confirmed()->create();

    expect($reservation->confirmed_at)->not->toBeNull()
        ->and($reservation->confirmed_by)->not->toBeNull();
});

it('reference_number is unique across reservations', function (): void {
    $a = Reservation::factory()->create();
    $b = Reservation::factory()->create();

    expect($a->reference_number)->not->toBe($b->reference_number);
});

it('reference_number follows expected format', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->reference_number)->toMatch('/^RES-\d{8}-\d{6}$/');
});

it('rejected reservation has rejection_reason and rejected_at set', function (): void {
    $reservation = Reservation::factory()->rejected()->create();

    expect($reservation->status)->toBe(ReservationStatus::Rejected)
        ->and($reservation->rejection_reason)->not->toBeNull()
        ->and($reservation->rejected_at)->not->toBeNull();
});

it('cancelled reservation has cancelled_at set and no cancellation_token', function (): void {
    $reservation = Reservation::factory()->cancelled()->create();

    expect($reservation->status)->toBe(ReservationStatus::Cancelled)
        ->and($reservation->cancelled_at)->not->toBeNull()
        ->and($reservation->cancellation_token)->toBeNull();
});

it('guest_count is cast to integer', function (): void {
    $reservation = Reservation::factory()->create(['guest_count' => 4]);

    expect($reservation->guest_count)->toBe(4)->toBeInt();
});

it('belongs to a user when user_id is set', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->user)->toBeNull();

    $reservation2 = Reservation::factory()->for(User::factory())->create();

    expect($reservation2->user)->not->toBeNull();
});
