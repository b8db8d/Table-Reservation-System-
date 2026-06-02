<?php

use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->manager = User::factory()->manager()->create();
});

it('manager can create a joining group with selected tables', function (): void {
    $tables = RestaurantTable::factory()->count(2)->create();

    $this->actingAs($this->manager)
        ->post('/admin/tables/groups', [
            'name' => 'Party Zone',
            'min_guests' => 5,
            'table_ids' => $tables->pluck('id')->toArray(),
        ])
        ->assertRedirectToRoute('admin.tables.groups.index');

    $group = TableJoiningGroup::where('name', 'Party Zone')->first();
    expect($group)->not->toBeNull()
        ->and($group->restaurantTables()->count())->toBe(2);
});

it('min_guests is required and positive', function (): void {
    $this->actingAs($this->manager)
        ->post('/admin/tables/groups', ['name' => 'Test', 'table_ids' => []])
        ->assertSessionHasErrors(['min_guests', 'table_ids']);
});

it('a table already in another group cannot be added', function (): void {
    $table = RestaurantTable::factory()->create();
    $existingGroup = TableJoiningGroup::factory()->create();
    $existingGroup->restaurantTables()->attach($table->id);

    $newTable = RestaurantTable::factory()->create();

    $this->actingAs($this->manager)
        ->post('/admin/tables/groups', [
            'min_guests' => 5,
            'table_ids' => [$table->id, $newTable->id],
        ])
        ->assertSessionHasErrors('table_ids');
});

it('deleting a group removes pivot entries and restrictions cascade-deleted', function (): void {
    $tables = RestaurantTable::factory()->count(2)->create();
    $group = TableJoiningGroup::factory()->create();
    $group->restaurantTables()->attach($tables->pluck('id'));
    $group->restrictions()->create([
        'day_of_week' => 1,
        'start_time' => '20:00:00',
        'end_time' => '22:00:00',
    ]);

    $this->actingAs($this->manager)
        ->delete("/admin/tables/groups/{$group->id}")
        ->assertRedirectToRoute('admin.tables.groups.index');

    expect(TableJoiningGroup::find($group->id))->toBeNull();

    foreach ($tables as $table) {
        expect($table->fresh()->joiningGroups()->count())->toBe(0);
    }
});

it('staff cannot access joining group management', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/tables/groups')
        ->assertForbidden();
});

it('manager can update a joining group', function (): void {
    $table = RestaurantTable::factory()->create();
    $group = TableJoiningGroup::factory()->create(['name' => 'Old Name', 'min_guests' => 5]);
    $group->restaurantTables()->attach($table->id);

    $newTable = RestaurantTable::factory()->create();

    $this->actingAs($this->manager)
        ->patch("/admin/tables/groups/{$group->id}", [
            'name' => 'New Name',
            'min_guests' => 6,
            'table_ids' => [$newTable->id],
        ])
        ->assertRedirectToRoute('admin.tables.groups.index');

    expect($group->fresh()->name)->toBe('New Name')
        ->and($group->fresh()->min_guests)->toBe(6)
        ->and($group->fresh()->restaurantTables()->pluck('id')->toArray())->toBe([$newTable->id]);
});
