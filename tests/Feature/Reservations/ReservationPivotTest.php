<?php

use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('can attach restaurant tables to a reservation', function (): void {
    $reservation = Reservation::factory()->create();
    $table = RestaurantTable::factory()->create();

    $reservation->restaurantTables()->attach($table);

    expect($reservation->restaurantTables()->count())->toBe(1)
        ->and($reservation->restaurantTables->first()->id)->toBe($table->id);
});

it('can attach multiple tables to one reservation', function (): void {
    $reservation = Reservation::factory()->create();
    $tables = RestaurantTable::factory()->count(3)->create();

    $reservation->restaurantTables()->attach($tables->pluck('id'));

    expect($reservation->restaurantTables()->count())->toBe(3);
});

it('is accessible from the table side via reservations relationship', function (): void {
    $reservation = Reservation::factory()->create();
    $table = RestaurantTable::factory()->create();

    $reservation->restaurantTables()->attach($table);

    expect($table->reservations()->count())->toBe(1)
        ->and($table->reservations->first()->id)->toBe($reservation->id);
});

it('pivot rows are deleted when reservation is deleted', function (): void {
    $reservation = Reservation::factory()->create();
    $table = RestaurantTable::factory()->create();
    $reservation->restaurantTables()->attach($table);

    $reservation->delete();

    expect(
        DB::table('reservation_restaurant_table')
            ->where('reservation_id', $reservation->id)
            ->count()
    )->toBe(0);
});

it('deleting a table with an attached reservation throws a restrict exception', function (): void {
    $reservation = Reservation::factory()->create();
    $table = RestaurantTable::factory()->create();
    $reservation->restaurantTables()->attach($table);

    expect(fn () => $table->delete())->toThrow(QueryException::class);
});

it('prevents duplicate pivot entries for the same reservation and table', function (): void {
    $reservation = Reservation::factory()->create();
    $table = RestaurantTable::factory()->create();
    $reservation->restaurantTables()->attach($table);

    expect(fn () => $reservation->restaurantTables()->attach($table))->toThrow(QueryException::class);
});
