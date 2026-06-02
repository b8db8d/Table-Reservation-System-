<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->customer = User::factory()->create();
});

it('authenticated customer sees their reservations', function (): void {
    Reservation::factory()->create(['email' => $this->customer->email]);

    $this->actingAs($this->customer)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('reservations', 1)
        );
});

it('reservations are matched by email even if created before account was made', function (): void {
    Reservation::factory()->count(3)->create([
        'email' => $this->customer->email,
        'user_id' => null,
    ]);

    $this->actingAs($this->customer)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('reservations', 3)
        );
});

it('confirmed upcoming reservations show cancellation link', function (): void {
    Reservation::factory()->confirmed()->create([
        'email' => $this->customer->email,
        'reservation_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($this->customer)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('reservations', 1)
            ->where('reservations.0.cancel_url', fn ($url) => str_contains($url, '/cancel/'))
        );
});

it('past and non-confirmed reservations do not show cancellation link', function (): void {
    Reservation::factory()->confirmed()->create([
        'email' => $this->customer->email,
        'reservation_date' => now()->subDay()->toDateString(),
    ]);
    Reservation::factory()->cancelled()->create(['email' => $this->customer->email]);
    Reservation::factory()->rejected()->create(['email' => $this->customer->email]);

    $this->actingAs($this->customer)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('reservations', 3)
            ->where('reservations.0.cancel_url', null)
            ->where('reservations.1.cancel_url', null)
            ->where('reservations.2.cancel_url', null)
        );
});

it("another customer's reservations are not visible", function (): void {
    $other = User::factory()->create();
    Reservation::factory()->count(2)->create(['email' => $other->email]);
    Reservation::factory()->create(['email' => $this->customer->email]);

    $this->actingAs($this->customer)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('reservations', 1)
        );
});
