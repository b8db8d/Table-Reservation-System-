<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->manager = User::factory()->manager()->create();
});

it('manager can view the staff list', function (): void {
    User::factory()->staff()->create(['name' => 'Alice']);

    $this->actingAs($this->manager)
        ->get('/admin/staff')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Staff/Index')
            ->has('staff')
        );
});

it('staff cannot access staff management', function (): void {
    $this->actingAs(User::factory()->staff()->create())
        ->get('/admin/staff')
        ->assertForbidden();
});

it('manager can create a staff user with staff role', function (): void {
    $this->actingAs($this->manager)
        ->post('/admin/staff', [
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => Role::Staff->value,
        ])
        ->assertRedirectToRoute('admin.staff.index');

    $user = User::where('email', 'bob@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(Role::Staff))->toBeTrue()
        ->and($user->is_active)->toBeTrue();
});

it('manager can create another manager', function (): void {
    $this->actingAs($this->manager)
        ->post('/admin/staff', [
            'name' => 'Carol Manager',
            'email' => 'carol@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => Role::Manager->value,
        ])
        ->assertRedirectToRoute('admin.staff.index');

    expect(User::where('email', 'carol@example.com')->first()->hasRole(Role::Manager))->toBeTrue();
});

it('creating a staff user with a duplicate email returns validation error', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($this->manager)
        ->post('/admin/staff', [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => Role::Staff->value,
        ])
        ->assertSessionHasErrors('email');
});

it('name, email, password, and role are required', function (): void {
    $this->actingAs($this->manager)
        ->post('/admin/staff', [])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

it('manager can deactivate a staff account', function (): void {
    $staff = User::factory()->staff()->create();

    $this->actingAs($this->manager)
        ->patch("/admin/staff/{$staff->id}/toggle-active")
        ->assertRedirect();

    expect($staff->fresh()->is_active)->toBeFalse();
});

it('manager can reactivate a deactivated account', function (): void {
    $staff = User::factory()->staff()->inactive()->create();

    $this->actingAs($this->manager)
        ->patch("/admin/staff/{$staff->id}/toggle-active")
        ->assertRedirect();

    expect($staff->fresh()->is_active)->toBeTrue();
});

it('deactivated user cannot log in', function (): void {
    User::factory()->staff()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors();

    $this->assertGuest();
});

it('created staff can log in and access admin panel', function (): void {
    $this->actingAs($this->manager)
        ->post('/admin/staff', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => Role::Staff->value,
        ]);

    $this->post('/login', [
        'email' => 'newstaff@example.com',
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticated();
});
