<?php

use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('manager can delete a reservation', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->delete("/admin/reservations/{$reservation->id}")
        ->assertRedirectToRoute('admin.reservations.index');

    expect(Reservation::find($reservation->id))->toBeNull();
});

it('staff cannot delete a reservation', function (): void {
    $reservation = Reservation::factory()->pending()->create();

    $this->actingAs(User::factory()->staff()->create())
        ->delete("/admin/reservations/{$reservation->id}")
        ->assertForbidden();
});

it('deleting a reservation releases its table assignments', function (): void {
    $table = RestaurantTable::factory()->create(['capacity' => 4]);
    $reservation = Reservation::factory()->confirmed()->create();
    $reservation->restaurantTables()->attach($table->id);

    $this->actingAs(User::factory()->manager()->create())
        ->delete("/admin/reservations/{$reservation->id}");

    expect(Reservation::find($reservation->id))->toBeNull()
        ->and($table->fresh()->reservations()->count())->toBe(0);
});
