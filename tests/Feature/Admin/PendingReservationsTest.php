<?php

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows staff to view pending reservations', function (): void {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get('/admin/reservations/pending')->assertOk();
});

it('allows manager to view pending reservations', function (): void {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)->get('/admin/reservations/pending')->assertOk();
});

it('denies customer access to pending reservations', function (): void {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get('/admin/reservations/pending')->assertForbidden();
});

it('redirects unauthenticated user to login', function (): void {
    $this->get('/admin/reservations/pending')->assertRedirect('/login');
});

it('returns only pending reservations', function (): void {
    Reservation::factory()->pending()->count(3)->create();
    Reservation::factory()->confirmed()->count(2)->create();
    Reservation::factory()->rejected()->create();
    Reservation::factory()->cancelled()->create();

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get('/admin/reservations/pending')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Reservations/Pending')
            ->has('reservations', 3)
        );
});

it('returns reservations sorted oldest first', function (): void {
    $old = Reservation::factory()->pending()->create(['created_at' => now()->subDays(3)]);
    $newer = Reservation::factory()->pending()->create(['created_at' => now()->subDay()]);
    $newest = Reservation::factory()->pending()->create(['created_at' => now()]);

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get('/admin/reservations/pending')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Reservations/Pending')
            ->where('reservations.0.reference_number', $old->reference_number)
            ->where('reservations.1.reference_number', $newer->reference_number)
            ->where('reservations.2.reference_number', $newest->reference_number)
        );
});
