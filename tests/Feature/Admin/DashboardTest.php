<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows manager to access admin dashboard', function (): void {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)->get('/admin')->assertOk();
});

it('allows staff to access admin dashboard', function (): void {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get('/admin')->assertOk();
});

it('denies customer access to admin dashboard', function (): void {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get('/admin')->assertForbidden();
});

it('redirects unauthenticated user to login', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

it('shows correct count of pending reservations', function (): void {
    Reservation::factory()->pending()->count(3)->create();
    Reservation::factory()->confirmed()->count(2)->create();

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get('/admin')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('pendingCount', 3)
        );
});

it('shows today confirmed reservations', function (): void {
    $today = Reservation::factory()->confirmed()->create([
        'reservation_date' => today()->toDateString(),
    ]);
    Reservation::factory()->confirmed()->create([
        'reservation_date' => today()->addDays(2)->toDateString(),
    ]);

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get('/admin')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('todayReservations', 1)
            ->where('todayReservations.0.reference_number', $today->reference_number)
        );
});

it('shows tomorrow confirmed reservations', function (): void {
    $tomorrow = Reservation::factory()->confirmed()->create([
        'reservation_date' => today()->addDay()->toDateString(),
    ]);
    Reservation::factory()->confirmed()->create([
        'reservation_date' => today()->toDateString(),
    ]);

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get('/admin')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('tomorrowReservations', 1)
            ->where('tomorrowReservations.0.reference_number', $tomorrow->reference_number)
        );
});
