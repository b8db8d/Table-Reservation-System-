<?php

use App\Models\RestaurantTable;
use Illuminate\Database\QueryException;

it('can be created with valid fields', function (): void {
    $table = RestaurantTable::factory()->create([
        'name' => 'Table 1',
        'capacity' => 4,
    ]);

    expect($table->name)->toBe('Table 1')
        ->and($table->capacity)->toBe(4)
        ->and($table->is_active)->toBeTrue();
});

it('defaults is_active to true', function (): void {
    $table = RestaurantTable::factory()->create();

    expect($table->is_active)->toBeTrue();
});

it('can be marked inactive', function (): void {
    $table = RestaurantTable::factory()->inactive()->create();

    expect($table->is_active)->toBeFalse();
});

it('active scope returns only active tables', function (): void {
    RestaurantTable::factory()->count(3)->create();
    RestaurantTable::factory()->inactive()->count(2)->create();

    expect(RestaurantTable::active()->count())->toBe(3);
});

it('capacity is cast to integer', function (): void {
    $table = RestaurantTable::factory()->withCapacity(6)->create();

    expect($table->capacity)->toBe(6)
        ->and($table->capacity)->toBeInt();
});

it('name is required', function (): void {
    expect(fn () => RestaurantTable::factory()->create(['name' => null]))
        ->toThrow(QueryException::class);
});

it('capacity must be a positive value', function (): void {
    $table = RestaurantTable::factory()->withCapacity(2)->create();

    expect($table->capacity)->toBeGreaterThan(0);
});
