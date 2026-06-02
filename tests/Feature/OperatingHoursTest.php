<?php

use App\Models\OperatingHours;
use Carbon\Carbon;
use Database\Seeders\OperatingHoursSeeder;

it('seeder inserts seven rows', function (): void {
    $this->seed(OperatingHoursSeeder::class);

    expect(OperatingHours::count())->toBe(7);
});

it('each day_of_week is unique after seeding', function (): void {
    $this->seed(OperatingHoursSeeder::class);

    $days = OperatingHours::pluck('day_of_week')->toArray();

    expect(array_unique($days))->toHaveCount(7);
});

it('a closed day returns is_closed true', function (): void {
    $record = OperatingHours::factory()->forDay(1)->closed()->create();

    expect($record->is_closed)->toBeTrue()
        ->and($record->open_time)->toBeNull()
        ->and($record->close_time)->toBeNull();
});

it('day_of_week is cast to integer', function (): void {
    $record = OperatingHours::factory()->forDay(3)->create();

    expect($record->day_of_week)->toBe(3)->toBeInt();
});

it('is_closed is cast to boolean', function (): void {
    $record = OperatingHours::factory()->create();

    expect($record->is_closed)->toBeBool();
});

describe('isOpen', function (): void {
    it('returns true when time is within open hours', function (): void {
        $record = OperatingHours::factory()
            ->forDay(1)
            ->openBetween('12:00:00', '22:00:00')
            ->create();

        $time = Carbon::parse('next Monday')->setTime(15, 0);

        expect($record->isOpen($time))->toBeTrue();
    });

    it('returns false when time is outside open hours', function (): void {
        $record = OperatingHours::factory()
            ->forDay(1)
            ->openBetween('12:00:00', '22:00:00')
            ->create();

        $time = Carbon::parse('next Monday')->setTime(11, 0);

        expect($record->isOpen($time))->toBeFalse();
    });

    it('returns false when is_closed is true', function (): void {
        $record = OperatingHours::factory()->forDay(1)->closed()->create();

        $time = Carbon::parse('next Monday')->setTime(15, 0);

        expect($record->isOpen($time))->toBeFalse();
    });

    it('returns false when time is exactly at close_time boundary', function (): void {
        $record = OperatingHours::factory()
            ->forDay(1)
            ->openBetween('12:00:00', '22:00:00')
            ->create();

        $time = Carbon::parse('next Monday')->setTime(22, 0);

        expect($record->isOpen($time))->toBeFalse();
    });
});
