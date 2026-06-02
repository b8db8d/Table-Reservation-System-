<?php

use App\Enums\ReservationStatus;
use App\Exceptions\NoTableAvailableException;
use App\Models\JoiningGroupRestriction;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use App\Services\TableAssignmentService;

beforeEach(function (): void {
    $this->service = app(TableAssignmentService::class);
});

it('assigns the smallest sufficient individual table', function (): void {
    $small = RestaurantTable::factory()->withCapacity(2)->create();
    $large = RestaurantTable::factory()->withCapacity(6)->create();

    $reservation = Reservation::factory()->create([
        'guest_count' => 2,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00',
    ]);

    $this->service->assignTables($reservation);

    expect($reservation->restaurantTables()->pluck('id')->toArray())->toBe([$small->id]);
});

it('assigns a larger table when the smallest does not fit', function (): void {
    RestaurantTable::factory()->withCapacity(2)->create();
    $big = RestaurantTable::factory()->withCapacity(6)->create();

    $reservation = Reservation::factory()->create([
        'guest_count' => 5,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00',
    ]);

    $this->service->assignTables($reservation);

    expect($reservation->restaurantTables()->pluck('id')->toArray())->toBe([$big->id]);
});

it('falls back to a joining group when no individual table fits', function (): void {
    RestaurantTable::factory()->withCapacity(4)->create();
    [$t1, $t2] = RestaurantTable::factory()->withCapacity(4)->count(2)->create();

    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();
    $group->restaurantTables()->attach([$t1->id, $t2->id]);

    $reservation = Reservation::factory()->create([
        'guest_count' => 7,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00',
    ]);

    $this->service->assignTables($reservation);

    $assignedIds = $reservation->restaurantTables()->pluck('id')->sort()->values();
    expect($assignedIds->toArray())->toBe(collect([$t1->id, $t2->id])->sort()->values()->toArray());
});

it('throws NoTableAvailableException when fully booked', function (): void {
    $table = RestaurantTable::factory()->withCapacity(4)->create();

    $existing = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'guest_count' => 4,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $existing->restaurantTables()->attach($table);

    $reservation = Reservation::factory()->create([
        'guest_count' => 2,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00',
    ]);

    expect(fn () => $this->service->assignTables($reservation))
        ->toThrow(NoTableAvailableException::class);
});

it('does not use a joining group when guest_count is below min_guests', function (): void {
    // Individual tables too small (capacity 2 < 5), group has min_guests 6 > 5
    [$t1, $t2] = RestaurantTable::factory()->withCapacity(2)->count(2)->create();

    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();
    $group->restaurantTables()->attach([$t1->id, $t2->id]);

    $reservation = Reservation::factory()->create([
        'guest_count' => 5, // too many for individual tables, but below group min_guests of 6
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00',
    ]);

    expect(fn () => $this->service->assignTables($reservation))
        ->toThrow(NoTableAvailableException::class);
});

it('respects joining group restrictions by day and time', function (): void {
    [$t1, $t2] = RestaurantTable::factory()->withCapacity(4)->count(2)->create();

    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();
    $group->restaurantTables()->attach([$t1->id, $t2->id]);

    // Restrict every day 18:00–21:00
    JoiningGroupRestriction::factory()
        ->for($group, 'tableJoiningGroup')
        ->everyDay()
        ->betweenHours(18, 21)
        ->create();

    $reservation = Reservation::factory()->create([
        'guest_count' => 7,
        'reservation_date' => now()->addDay()->toDateString(),
        'reservation_time' => '19:00:00', // within the restricted window
    ]);

    expect(fn () => $this->service->assignTables($reservation))
        ->toThrow(NoTableAvailableException::class);
});
