<?php

use App\Models\JoiningGroupRestriction;
use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->manager = User::factory()->manager()->create();
    $this->group = TableJoiningGroup::factory()->create(['min_guests' => 5]);
});

it('manager can view restrictions page for a group', function (): void {
    $this->actingAs($this->manager)
        ->get("/admin/tables/groups/{$this->group->id}/restrictions")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Tables/Groups/Restrictions'));
});

it('staff cannot access restrictions page', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get("/admin/tables/groups/{$this->group->id}/restrictions")
        ->assertForbidden();
});

it('restriction can be added to a group for a specific day and time', function (): void {
    $this->actingAs($this->manager)
        ->post("/admin/tables/groups/{$this->group->id}/restrictions", [
            'day_of_week' => 1,
            'start_time' => '20:00',
            'end_time' => '22:00',
        ])
        ->assertRedirect();

    expect($this->group->restrictions()->count())->toBe(1);

    $restriction = $this->group->restrictions()->first();
    expect($restriction->day_of_week)->toBe(1)
        ->and($restriction->start_time)->toBe('20:00:00')
        ->and($restriction->end_time)->toBe('22:00:00');
});

it('restriction with day_of_week null applies every day', function (): void {
    $this->actingAs($this->manager)
        ->post("/admin/tables/groups/{$this->group->id}/restrictions", [
            'day_of_week' => null,
            'start_time' => '14:00',
            'end_time' => '16:00',
        ])
        ->assertRedirect();

    $restriction = $this->group->restrictions()->first();
    expect($restriction->day_of_week)->toBeNull();
});

it('start_time must be before end_time', function (): void {
    $this->actingAs($this->manager)
        ->post("/admin/tables/groups/{$this->group->id}/restrictions", [
            'day_of_week' => 2,
            'start_time' => '22:00',
            'end_time' => '20:00',
        ])
        ->assertSessionHasErrors('end_time');
});

it('duplicate restriction with overlapping time returns validation error', function (): void {
    $this->group->restrictions()->create([
        'day_of_week' => 1,
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
    ]);

    $this->actingAs($this->manager)
        ->post("/admin/tables/groups/{$this->group->id}/restrictions", [
            'day_of_week' => 1,
            'start_time' => '19:00',
            'end_time' => '21:00',
        ])
        ->assertSessionHasErrors('start_time');
});

it('null day restriction overlaps with any specific day', function (): void {
    $this->group->restrictions()->create([
        'day_of_week' => null,
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
    ]);

    $this->actingAs($this->manager)
        ->post("/admin/tables/groups/{$this->group->id}/restrictions", [
            'day_of_week' => 3,
            'start_time' => '19:00',
            'end_time' => '21:00',
        ])
        ->assertSessionHasErrors('start_time');
});

it('manager can remove a restriction', function (): void {
    $restriction = $this->group->restrictions()->create([
        'day_of_week' => 1,
        'start_time' => '20:00:00',
        'end_time' => '22:00:00',
    ]);

    $this->actingAs($this->manager)
        ->delete("/admin/tables/groups/{$this->group->id}/restrictions/{$restriction->id}")
        ->assertRedirect();

    expect(JoiningGroupRestriction::find($restriction->id))->toBeNull();
});

it('cannot delete a restriction belonging to another group', function (): void {
    $otherGroup = TableJoiningGroup::factory()->create();
    $restriction = $otherGroup->restrictions()->create([
        'day_of_week' => 1,
        'start_time' => '20:00:00',
        'end_time' => '22:00:00',
    ]);

    $this->actingAs($this->manager)
        ->delete("/admin/tables/groups/{$this->group->id}/restrictions/{$restriction->id}")
        ->assertNotFound();
});

it('AvailabilityService excludes group during restricted window', function (): void {
    $table = RestaurantTable::factory()->create(['capacity' => 8, 'is_active' => true]);
    $this->group->restaurantTables()->attach($table->id);

    $this->group->restrictions()->create([
        'day_of_week' => null,
        'start_time' => '18:00:00',
        'end_time' => '22:00:00',
    ]);

    $service = app(AvailabilityService::class);
    $date = Carbon::now()->addDay();
    $restrictedTime = Carbon::createFromTime(19, 0, 0);
    $freeTime = Carbon::createFromTime(14, 0, 0);

    $restricted = $service->getAvailableCapacity($date, $restrictedTime);
    $free = $service->getAvailableCapacity($date, $freeTime);

    expect($restricted['groups']->pluck('id')->contains($this->group->id))->toBeFalse()
        ->and($free['groups']->pluck('id')->contains($this->group->id))->toBeTrue();
});
