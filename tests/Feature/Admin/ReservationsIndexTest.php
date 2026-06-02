<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows staff to access the reservations index', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/reservations')
        ->assertOk();
});

it('allows manager to access the reservations index', function (): void {
    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations')
        ->assertOk();
});

it('denies customer access to reservations index', function (): void {
    $this->actingAs(User::factory()->customer()->create())
        ->get('/admin/reservations')
        ->assertForbidden();
});

it('redirects unauthenticated user to login', function (): void {
    $this->get('/admin/reservations')->assertRedirect('/login');
});

it('returns a paginated list of all reservations', function (): void {
    Reservation::factory()->pending()->count(3)->create();
    Reservation::factory()->confirmed()->count(2)->create();

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Reservations/Index')
            ->has('reservations.data', 5)
            ->has('reservations.total')
        );
});

it('filters by status', function (): void {
    Reservation::factory()->pending()->count(2)->create();
    Reservation::factory()->confirmed()->count(3)->create();

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations?filter[status]=pending')
        ->assertInertia(fn ($page) => $page
            ->has('reservations.data', 2)
            ->where('reservations.data.0.status', 'pending')
        );
});

it('filters by date range', function (): void {
    Reservation::factory()->create(['reservation_date' => '2025-06-01']);
    Reservation::factory()->create(['reservation_date' => '2025-06-15']);
    Reservation::factory()->create(['reservation_date' => '2025-07-01']);

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations?filter[date_from]=2025-06-01&filter[date_to]=2025-06-30')
        ->assertInertia(fn ($page) => $page
            ->has('reservations.data', 2)
        );
});

it('searches by email', function (): void {
    Reservation::factory()->create(['email' => 'alice@example.com']);
    Reservation::factory()->create(['email' => 'bob@other.com']);

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations?filter[search]=alice')
        ->assertInertia(fn ($page) => $page
            ->has('reservations.data', 1)
            ->where('reservations.data.0.email', 'alice@example.com')
        );
});

it('searches by reference number', function (): void {
    $reservation = Reservation::factory()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->get("/admin/reservations?filter[search]={$reservation->reference_number}")
        ->assertInertia(fn ($page) => $page
            ->has('reservations.data', 1)
            ->where('reservations.data.0.reference_number', $reservation->reference_number)
        );
});

it('sorts by reservation date ascending', function (): void {
    $first = Reservation::factory()->create(['reservation_date' => '2025-06-01']);
    $second = Reservation::factory()->create(['reservation_date' => '2025-06-15']);

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations?sort=reservation_date')
        ->assertInertia(fn ($page) => $page
            ->where('reservations.data.0.reservation_date', '2025-06-01')
            ->where('reservations.data.1.reservation_date', '2025-06-15')
        );
});

it('sorts by reservation date descending', function (): void {
    $first = Reservation::factory()->create(['reservation_date' => '2025-06-01']);
    $second = Reservation::factory()->create(['reservation_date' => '2025-06-15']);

    $this->actingAs(User::factory()->manager()->create())
        ->get('/admin/reservations?sort=-reservation_date')
        ->assertInertia(fn ($page) => $page
            ->where('reservations.data.0.reservation_date', '2025-06-15')
            ->where('reservations.data.1.reservation_date', '2025-06-01')
        );
});
