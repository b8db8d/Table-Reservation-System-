<?php

use App\Models\OperatingHours;
use App\Models\User;
use Database\Seeders\OperatingHoursSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OperatingHoursSeeder::class);
    $this->manager = User::factory()->manager()->create();
});

function allHoursPayload(?array $overrides = null): array
{
    $rows = OperatingHours::orderBy('day_of_week')->get()->map(fn ($h) => [
        'day_of_week' => $h->day_of_week,
        'is_closed' => $h->is_closed,
        'open_time' => $h->open_time ? substr($h->open_time, 0, 5) : '12:00',
        'close_time' => $h->close_time ? substr($h->close_time, 0, 5) : '22:00',
    ])->toArray();

    if ($overrides !== null) {
        foreach ($rows as &$row) {
            if ($row['day_of_week'] === $overrides['day_of_week']) {
                $row = array_merge($row, $overrides);
            }
        }
    }

    return $rows;
}

it('manager can view operating hours page', function (): void {
    $this->actingAs($this->manager)
        ->get('/admin/settings/operating-hours')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/OperatingHours')
            ->has('hours', 7)
        );
});

it('staff cannot access operating hours page', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/settings/operating-hours')
        ->assertForbidden();
});

it('manager can update hours for a specific day', function (): void {
    $this->actingAs($this->manager)
        ->patch('/admin/settings/operating-hours', [
            'hours' => allHoursPayload(['day_of_week' => 1, 'open_time' => '10:00', 'close_time' => '21:00', 'is_closed' => false]),
        ])
        ->assertRedirect();

    $monday = OperatingHours::where('day_of_week', 1)->first();
    expect($monday->open_time)->toBe('10:00:00')
        ->and($monday->close_time)->toBe('21:00:00');
});

it('marking a day as closed sets is_closed to true and nulls the times', function (): void {
    $this->actingAs($this->manager)
        ->patch('/admin/settings/operating-hours', [
            'hours' => allHoursPayload(['day_of_week' => 1, 'is_closed' => true, 'open_time' => '12:00', 'close_time' => '22:00']),
        ])
        ->assertRedirect();

    $monday = OperatingHours::where('day_of_week', 1)->first();
    expect($monday->is_closed)->toBeTrue()
        ->and($monday->open_time)->toBeNull()
        ->and($monday->close_time)->toBeNull();
});

it('open_time must be before close_time', function (): void {
    $this->actingAs($this->manager)
        ->patch('/admin/settings/operating-hours', [
            'hours' => allHoursPayload(['day_of_week' => 1, 'open_time' => '22:00', 'close_time' => '10:00', 'is_closed' => false]),
        ])
        ->assertSessionHasErrors('hours.1.close_time');
});

it('open_time and close_time are required when not closed', function (): void {
    $payload = allHoursPayload();
    $payload[1]['is_closed'] = false;
    $payload[1]['open_time'] = '';
    $payload[1]['close_time'] = '';

    $this->actingAs($this->manager)
        ->patch('/admin/settings/operating-hours', ['hours' => $payload])
        ->assertSessionHasErrors();
});

it('changes to operating hours immediately affect the availability API', function (): void {
    // Monday = day_of_week 1; close it
    $this->actingAs($this->manager)
        ->patch('/admin/settings/operating-hours', [
            'hours' => allHoursPayload(['day_of_week' => 1, 'is_closed' => true, 'open_time' => '12:00', 'close_time' => '22:00']),
        ]);

    // Find the next Monday
    $nextMonday = now()->next('Monday')->toDateString();

    $this->getJson("/api/availability?date={$nextMonday}&time=14:00&guests=2")
        ->assertJsonValidationErrors('time');
});
