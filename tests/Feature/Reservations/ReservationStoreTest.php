<?php

use App\Enums\ReservationStatus;
use App\Events\ReservationCreated;
use App\Models\OperatingHours;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    // Only fake the specific event so Eloquent model observers still run
    Event::fake([ReservationCreated::class]);

    foreach (range(1, 5) as $day) {
        OperatingHours::factory()->create([
            'day_of_week' => $day,
            'open_time' => '12:00:00',
            'close_time' => '22:00:00',
            'is_closed' => false,
        ]);
    }
    foreach ([0, 6] as $day) {
        OperatingHours::factory()->create([
            'day_of_week' => $day,
            'open_time' => null,
            'close_time' => null,
            'is_closed' => true,
        ]);
    }

    RestaurantTable::factory()->create(['capacity' => 4]);

    $this->date = Carbon::parse('next Monday')->toDateString();
    $this->time = '18:00';

    $this->validPayload = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+48123456789',
        'guest_count' => 2,
        'reservation_date' => $this->date,
        'reservation_time' => $this->time,
        'notes' => null,
    ];
});

it('creates a reservation in pending status for a valid submission', function (): void {
    $response = $this->post('/reservations', $this->validPayload);

    $response->assertRedirectToRoute('reservations.success');
    expect(Reservation::count())->toBe(1)
        ->and(Reservation::first()->status)->toBe(ReservationStatus::Pending);
});

it('redirects to success page with reference number in session', function (): void {
    $this->post('/reservations', $this->validPayload)
        ->assertRedirectToRoute('reservations.success')
        ->assertSessionHas('reference_number');

    expect(session('reference_number'))->toMatch('/^RES-\d{8}-\d{6}$/');
});

it('links the reservation to the authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/reservations', $this->validPayload);

    expect(Reservation::first()->user_id)->toBe($user->id);
});

it('dispatches the ReservationCreated event', function (): void {
    $this->post('/reservations', $this->validPayload);

    Event::assertDispatched(ReservationCreated::class, function ($event) {
        return $event->reservation->email === 'john@example.com';
    });
});

it('assigns a table to the reservation', function (): void {
    $this->post('/reservations', $this->validPayload);

    expect(Reservation::first()->restaurantTables)->toHaveCount(1);
});

it('returns a validation error for an invalid phone number', function (): void {
    $this->post('/reservations', array_merge($this->validPayload, ['phone' => 'not-a-phone']))
        ->assertSessionHasErrors('phone');
});

it('returns a validation error for a past date', function (): void {
    $this->post('/reservations', array_merge($this->validPayload, ['reservation_date' => '2000-01-01']))
        ->assertSessionHasErrors('reservation_date');
});

it('returns a validation error for a time outside operating hours', function (): void {
    $this->post('/reservations', array_merge($this->validPayload, ['reservation_time' => '23:00']))
        ->assertSessionHasErrors('reservation_time');
});

it('returns an error when no table is available for the slot', function (): void {
    $table = RestaurantTable::first();

    $confirmed = Reservation::factory()->create([
        'status' => ReservationStatus::Confirmed,
        'reservation_date' => $this->date,
        'reservation_time' => $this->time.':00',
        'confirmed_by' => null,
        'confirmed_at' => now(),
    ]);
    $confirmed->restaurantTables()->attach($table);

    $this->post('/reservations', $this->validPayload)
        ->assertSessionHasErrors('guest_count');
});

it('returns a validation error for guest count of 0', function (): void {
    $this->post('/reservations', array_merge($this->validPayload, ['guest_count' => 0]))
        ->assertSessionHasErrors('guest_count');
});

it('returns a validation error for a negative guest count', function (): void {
    $this->post('/reservations', array_merge($this->validPayload, ['guest_count' => -1]))
        ->assertSessionHasErrors('guest_count');
});

it('rate limits to 3 submissions per IP in 10 minutes', function (): void {
    RateLimiter::clear('reservations');

    // Extra tables so the first three go through
    RestaurantTable::factory()->count(2)->create(['capacity' => 4]);

    $this->post('/reservations', $this->validPayload)->assertRedirectToRoute('reservations.success');
    $this->post('/reservations', $this->validPayload)->assertRedirectToRoute('reservations.success');
    $this->post('/reservations', $this->validPayload)->assertRedirectToRoute('reservations.success');

    $this->post('/reservations', $this->validPayload)->assertStatus(429);
});

it('rejects a submission when the honeypot name field is filled', function (): void {
    // Disable randomization so we can target the field name directly
    config(['honeypot.randomize_name_field_name' => false]);
    $honeypotFieldName = config('honeypot.name_field_name');

    $this->post('/reservations', array_merge($this->validPayload, [
        $honeypotFieldName => 'bot-value',
    ]))->assertStatus(422);

    expect(Reservation::count())->toBe(0);
});
