<?php

use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use Illuminate\Database\QueryException;

it('can be created with required fields', function (): void {
    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();

    expect($group->min_guests)->toBe(6)
        ->and($group->name)->toBeNull();
});

it('can be created with an optional name', function (): void {
    $group = TableJoiningGroup::factory()->named('Weekend large group')->create();

    expect($group->name)->toBe('Weekend large group');
});

it('min_guests is cast to integer', function (): void {
    $group = TableJoiningGroup::factory()->withMinGuests(5)->create();

    expect($group->min_guests)->toBe(5)->toBeInt();
});

it('can attach restaurant tables', function (): void {
    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();
    $tables = RestaurantTable::factory()->count(2)->withCapacity(4)->create();

    $group->restaurantTables()->attach($tables->pluck('id'));

    expect($group->restaurantTables()->count())->toBe(2);
});

it('computes combined capacity from attached tables', function (): void {
    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();
    RestaurantTable::factory()->withCapacity(4)->count(2)->create()
        ->each(fn ($t) => $group->restaurantTables()->attach($t->id));

    $group->load('restaurantTables');

    expect($group->combinedCapacity())->toBe(8);
});

it('enforces unique table membership via composite primary key', function (): void {
    $group = TableJoiningGroup::factory()->create();
    $table = RestaurantTable::factory()->create();

    $group->restaurantTables()->attach($table->id);

    expect(fn () => $group->restaurantTables()->attach($table->id))
        ->toThrow(QueryException::class);
});

it('a table belongs to the correct group via pivot', function (): void {
    $group = TableJoiningGroup::factory()->withMinGuests(6)->create();
    $table = RestaurantTable::factory()->withCapacity(4)->create();

    $group->restaurantTables()->attach($table->id);

    expect($table->joiningGroups()->first()->id)->toBe($group->id);
});

it('deleting a group cascades to pivot rows', function (): void {
    $group = TableJoiningGroup::factory()->create();
    $tables = RestaurantTable::factory()->count(2)->create();
    $group->restaurantTables()->attach($tables->pluck('id'));

    $group->delete();

    expect(DB::table('table_joining_group_restaurant_table')
        ->where('table_joining_group_id', $group->id)
        ->count()
    )->toBe(0);
});
