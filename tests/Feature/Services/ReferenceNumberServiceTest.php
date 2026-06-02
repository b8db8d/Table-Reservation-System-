<?php

use App\Models\Reservation;
use App\Services\ReferenceNumberService;

beforeEach(function (): void {
    $this->service = app(ReferenceNumberService::class);
});

it('generates a reference number in RES-YYYYMMDD-XXXXXX format', function (): void {
    $ref = $this->service->generate();

    expect($ref)->toMatch('/^RES-\d{8}-\d{6}$/');
});

it('includes today\'s date in the reference number', function (): void {
    $ref = $this->service->generate();

    expect($ref)->toContain('RES-'.now()->format('Ymd').'-');
});

it('generates unique reference numbers on repeated calls', function (): void {
    $refs = collect(range(1, 20))->map(fn () => $this->service->generate());

    expect($refs->unique()->count())->toBe(20);
});

it('auto-generates reference number when creating a reservation', function (): void {
    $reservation = Reservation::factory()->create();

    expect($reservation->reference_number)->toMatch('/^RES-\d{8}-\d{6}$/');
});

it('does not overwrite an explicit reference number', function (): void {
    $reservation = Reservation::factory()->create([
        'reference_number' => 'RES-20240101-000001',
    ]);

    expect($reservation->reference_number)->toBe('RES-20240101-000001');
});

it('generates distinct reference numbers across multiple reservations', function (): void {
    $refs = Reservation::factory()->count(10)->create()->pluck('reference_number');

    expect($refs->unique()->count())->toBe(10);
});
