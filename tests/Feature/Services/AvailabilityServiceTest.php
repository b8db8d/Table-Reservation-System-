<?php

use App\Enums\ReservationStatus;
use App\Models\JoiningGroupRestriction;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use App\Services\AvailabilityService;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->service = app(AvailabilityService::class);
    $this->date = Carbon::parse('next Monday');
    $this->time = Carbon::parse('next Monday')->setTime(19, 0);
});

it('returns all active tables when no reservations exist', function (): void {
    $tables = RestaurantTable::factory()->count(3)->create();

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['tables'])->toHaveCount(3)
        ->and($result['tables']->pluck('id')->sort()->values())
        ->toEqual($tables->pluck('id')->sort()->values());
});

it('excludes inactive tables', function (): void {
    RestaurantTable::factory()->create();
    RestaurantTable::factory()->inactive()->create();

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['tables'])->toHaveCount(1);
});

it('excludes tables already confirmed for that slot', function (): void {
    $freeTable = RestaurantTable::factory()->create();
    $bookedTable = RestaurantTable::factory()->create();

    $reservation = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'reservation_date' => $this->date->toDateString(),
        'reservation_time' => '19:00:00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $reservation->restaurantTables()->attach($bookedTable);

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['tables'])->toHaveCount(1)
        ->and($result['tables']->first()->id)->toBe($freeTable->id);
});

it('does not exclude tables with only pending reservations for that slot', function (): void {
    $table = RestaurantTable::factory()->create();
    $reservation = Reservation::factory()->pending()->create([
        'reservation_date' => $this->date->toDateString(),
        'reservation_time' => '19:00:00',
    ]);
    $reservation->restaurantTables()->attach($table);

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['tables'])->toHaveCount(1);
});

it('does not exclude a table confirmed at a different time on the same day', function (): void {
    $table = RestaurantTable::factory()->create();
    $reservation = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'reservation_date' => $this->date->toDateString(),
        'reservation_time' => '21:00:00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $reservation->restaurantTables()->attach($table);

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['tables'])->toHaveCount(1);
});

it('returns available joining groups when all their tables are free', function (): void {
    $tables = RestaurantTable::factory()->count(2)->create();
    $group = TableJoiningGroup::factory()->create();
    $group->restaurantTables()->attach($tables);

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['groups'])->toHaveCount(1)
        ->and($result['groups']->first()->id)->toBe($group->id);
});

it('excludes joining group when one of its tables is already booked', function (): void {
    [$tableA, $tableB] = RestaurantTable::factory()->count(2)->create();
    $group = TableJoiningGroup::factory()->create();
    $group->restaurantTables()->attach([$tableA->id, $tableB->id]);

    $reservation = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'reservation_date' => $this->date->toDateString(),
        'reservation_time' => '19:00:00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $reservation->restaurantTables()->attach($tableA);

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['groups'])->toHaveCount(0);
});

it('excludes joining group when a restriction applies on that day and time', function (): void {
    $tables = RestaurantTable::factory()->count(2)->create();
    $group = TableJoiningGroup::factory()->create();
    $group->restaurantTables()->attach($tables);

    // Monday = Carbon dayOfWeek 1, our slot is next Monday 19:00
    JoiningGroupRestriction::factory()
        ->for($group, 'tableJoiningGroup')
        ->forDay(1) // Monday
        ->betweenHours(18, 21)
        ->create();

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['groups'])->toHaveCount(0);
});

it('includes joining group when its restriction applies on a different day', function (): void {
    $tables = RestaurantTable::factory()->count(2)->create();
    $group = TableJoiningGroup::factory()->create();
    $group->restaurantTables()->attach($tables);

    // Restriction on Tuesday; our slot is Monday
    JoiningGroupRestriction::factory()
        ->for($group, 'tableJoiningGroup')
        ->forDay(2) // Tuesday
        ->betweenHours(18, 21)
        ->create();

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['groups'])->toHaveCount(1);
});

it('returns empty tables and groups when no capacity is available', function (): void {
    $table = RestaurantTable::factory()->create();
    $reservation = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'reservation_date' => $this->date->toDateString(),
        'reservation_time' => '19:00:00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $reservation->restaurantTables()->attach($table);

    $result = $this->service->getAvailableCapacity($this->date, $this->time);

    expect($result['tables'])->toHaveCount(0)
        ->and($result['groups'])->toHaveCount(0);
});
