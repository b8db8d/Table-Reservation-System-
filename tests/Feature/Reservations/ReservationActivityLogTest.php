<?php

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs activity when reservation is created', function (): void {
    Reservation::factory()->create();

    expect(Activity::count())->toBe(1);
});

it('logs the correct event name on creation', function (): void {
    Reservation::factory()->create();

    expect(Activity::first()->event)->toBe('created');
});

it('logs status change when reservation is updated', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $reservation->update(['status' => ReservationStatus::Confirmed]);

    $log = Activity::where('event', 'updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->attribute_changes['attributes']['status'])->toBe('confirmed');
});

it('logs confirmed_by when set', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $staff = User::factory()->create();

    $reservation->update([
        'status' => ReservationStatus::Confirmed,
        'confirmed_by' => $staff->id,
        'confirmed_at' => now(),
    ]);

    $log = Activity::where('event', 'updated')->first();

    expect($log->attribute_changes['attributes']['confirmed_by'])->toBe($staff->id);
});

it('logs rejection_reason when reservation is rejected', function (): void {
    $reservation = Reservation::factory()->pending()->create();
    $staff = User::factory()->create();

    $reservation->update([
        'status' => ReservationStatus::Rejected,
        'rejected_by' => $staff->id,
        'rejected_at' => now(),
        'rejection_reason' => 'No tables available',
    ]);

    $log = Activity::where('event', 'updated')->first();

    expect($log->attribute_changes['attributes']['rejection_reason'])->toBe('No tables available');
});

it('does not log an update when no tracked attributes change', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $reservation->update(['notes' => 'Changed notes only']);

    expect(Activity::where('event', 'updated')->count())->toBe(0);
});
