<?php

use App\Enums\ReservationStatus;
use App\Models\OperatingHours;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\TableJoiningGroup;
use Carbon\Carbon;

beforeEach(function (): void {
    // Seed operating hours: Mon-Fri 12:00-22:00
    foreach (range(1, 5) as $day) {
        OperatingHours::factory()->create([
            'day_of_week' => $day,
            'open_time' => '12:00:00',
            'close_time' => '22:00:00',
            'is_closed' => false,
        ]);
    }

    // Saturday and Sunday closed
    foreach ([0, 6] as $day) {
        OperatingHours::factory()->create([
            'day_of_week' => $day,
            'open_time' => null,
            'close_time' => null,
            'is_closed' => true,
        ]);
    }

    // next Monday at 18:00
    $this->date = Carbon::parse('next Monday')->toDateString();
    $this->time = '18:00';
});

it('returns available when capacity exists', function (): void {
    RestaurantTable::factory()->create(['capacity' => 4]);

    $response = $this->getJson("/api/availability?date={$this->date}&time={$this->time}&guests=2");

    $response->assertOk()
        ->assertJson(['available' => true])
        ->assertJsonCount(1, 'individual_tables');
});

it('returns unavailable when fully booked', function (): void {
    $table = RestaurantTable::factory()->create(['capacity' => 2]);

    $reservation = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'reservation_date' => $this->date,
        'reservation_time' => $this->time.':00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $reservation->restaurantTables()->attach($table);

    $response = $this->getJson("/api/availability?date={$this->date}&time={$this->time}&guests=2");

    $response->assertOk()
        ->assertJson(['available' => false])
        ->assertJsonCount(0, 'individual_tables')
        ->assertJsonCount(0, 'joining_groups');
});

it('returns joining group when individual tables are insufficient', function (): void {
    [$tableA, $tableB] = RestaurantTable::factory()->count(2)->create(['capacity' => 4]);
    $group = TableJoiningGroup::factory()->create(['min_guests' => 5]);
    $group->restaurantTables()->attach([$tableA->id, $tableB->id]);

    $response = $this->getJson("/api/availability?date={$this->date}&time={$this->time}&guests=7");

    $response->assertOk()
        ->assertJson(['available' => true])
        ->assertJsonCount(1, 'joining_groups');
});

it('validates required parameters', function (): void {
    $response = $this->getJson('/api/availability');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'time', 'guests']);
});

it('rejects dates in the past', function (): void {
    $response = $this->getJson('/api/availability?date=2000-01-01&time=18:00&guests=2');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

it('rejects times outside operating hours', function (): void {
    $response = $this->getJson("/api/availability?date={$this->date}&time=23:00&guests=2");

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['time']);
});

it('rejects a closed day', function (): void {
    $sunday = Carbon::parse('next Sunday')->toDateString();

    $response = $this->getJson("/api/availability?date={$sunday}&time=18:00&guests=2");

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['time']);
});

it('rejects guests less than 1', function (): void {
    $response = $this->getJson("/api/availability?date={$this->date}&time={$this->time}&guests=0");

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['guests']);
});

it('does not include joining group below min_guests threshold', function (): void {
    [$tableA, $tableB] = RestaurantTable::factory()->count(2)->create(['capacity' => 4]);
    $group = TableJoiningGroup::factory()->create(['min_guests' => 5]);
    $group->restaurantTables()->attach([$tableA->id, $tableB->id]);

    // Request 2 guests — individual tables with capacity 4 each will match, but group requires 5+
    $response = $this->getJson("/api/availability?date={$this->date}&time={$this->time}&guests=2");

    $response->assertOk()
        ->assertJson(['available' => true])
        ->assertJsonCount(0, 'joining_groups');
});
