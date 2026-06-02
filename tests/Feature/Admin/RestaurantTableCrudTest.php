<?php

use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('manager can view the tables index', function (): void {
    RestaurantTable::factory()->create(['name' => 'Table 1', 'capacity' => 4]);

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/tables')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Tables/Index')
            ->has('tables', 1)
        );
});

it('staff cannot access table management', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/tables')
        ->assertForbidden();
});

it('manager can create a table', function (): void {
    $this->actingAs(User::factory()->manager()->create())
        ->post('/admin/tables', [
            'name' => 'Table 5',
            'capacity' => 6,
            'is_active' => true,
        ])
        ->assertRedirectToRoute('admin.tables.index');

    expect(RestaurantTable::where('name', 'Table 5')->exists())->toBeTrue();
});

it('name and capacity are required on create', function (): void {
    $this->actingAs(User::factory()->manager()->create())
        ->post('/admin/tables', [])
        ->assertSessionHasErrors(['name', 'capacity']);
});

it('manager can edit a table', function (): void {
    $table = RestaurantTable::factory()->create(['name' => 'Old Name', 'capacity' => 2]);

    $this->actingAs(User::factory()->manager()->create())
        ->patch("/admin/tables/{$table->id}", [
            'name' => 'New Name',
            'capacity' => 4,
            'is_active' => true,
        ])
        ->assertRedirectToRoute('admin.tables.index');

    expect($table->fresh()->name)->toBe('New Name')
        ->and($table->fresh()->capacity)->toBe(4);
});

it('manager can delete a table with no upcoming confirmed reservations', function (): void {
    $table = RestaurantTable::factory()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->delete("/admin/tables/{$table->id}")
        ->assertRedirectToRoute('admin.tables.index');

    expect(RestaurantTable::find($table->id))->toBeNull();
});

it('cannot delete a table with upcoming confirmed reservations', function (): void {
    $table = RestaurantTable::factory()->create(['capacity' => 4]);
    $reservation = Reservation::factory()->confirmed()->create([
        'reservation_date' => Carbon::tomorrow()->toDateString(),
    ]);
    $reservation->restaurantTables()->attach($table->id);

    $this->actingAs(User::factory()->manager()->create())
        ->delete("/admin/tables/{$table->id}")
        ->assertSessionHasErrors('table');

    expect(RestaurantTable::find($table->id))->not->toBeNull();
});

it('deactivating a table hides it from availability', function (): void {
    $table = RestaurantTable::factory()->create(['is_active' => true]);

    $this->actingAs(User::factory()->manager()->create())
        ->patch("/admin/tables/{$table->id}", [
            'name' => $table->name,
            'capacity' => $table->capacity,
            'is_active' => false,
        ]);

    expect($table->fresh()->is_active)->toBeFalse()
        ->and(RestaurantTable::active()->find($table->id))->toBeNull();
});
