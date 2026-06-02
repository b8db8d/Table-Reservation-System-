<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

describe('manager role', function (): void {
    it('has all permissions', function (): void {
        $manager = User::factory()->manager()->create();

        foreach (Permission::cases() as $permission) {
            expect($manager->can($permission->value))->toBeTrue("Manager should have '{$permission->value}'");
        }
    });
});

describe('staff role', function (): void {
    it('has reservation view and action permissions', function (): void {
        $staff = User::factory()->staff()->create();

        $allowed = [
            Permission::ReservationsViewAny,
            Permission::ReservationsView,
            Permission::ReservationsCreate,
            Permission::ReservationsUpdate,
            Permission::ReservationsConfirm,
            Permission::ReservationsReject,
        ];

        foreach ($allowed as $permission) {
            expect($staff->can($permission->value))->toBeTrue("Staff should have '{$permission->value}'");
        }
    });

    it('cannot manage tables, operating hours, or staff', function (): void {
        $staff = User::factory()->staff()->create();

        $denied = [
            Permission::ReservationsDelete,
            Permission::TablesManage,
            Permission::OperatingHoursManage,
            Permission::StaffManage,
        ];

        foreach ($denied as $permission) {
            expect($staff->can($permission->value))->toBeFalse("Staff should not have '{$permission->value}'");
        }
    });
});

describe('customer role', function (): void {
    it('can only view own reservations', function (): void {
        $customer = User::factory()->customer()->create();

        expect($customer->can(Permission::ReservationsView->value))->toBeTrue();
    });

    it('has no admin permissions', function (): void {
        $customer = User::factory()->customer()->create();

        $denied = [
            Permission::ReservationsViewAny,
            Permission::ReservationsCreate,
            Permission::ReservationsUpdate,
            Permission::ReservationsDelete,
            Permission::ReservationsConfirm,
            Permission::ReservationsReject,
            Permission::TablesManage,
            Permission::OperatingHoursManage,
            Permission::StaffManage,
        ];

        foreach ($denied as $permission) {
            expect($customer->can($permission->value))->toBeFalse("Customer should not have '{$permission->value}'");
        }
    });
});

it('unauthenticated user is not logged in', function (): void {
    expect(auth()->check())->toBeFalse();
});

it('assigns roles via factory states', function (): void {
    $manager = User::factory()->manager()->create();
    $staff = User::factory()->staff()->create();
    $customer = User::factory()->customer()->create();

    expect($manager->hasRole(Role::Manager->value))->toBeTrue();
    expect($staff->hasRole(Role::Staff->value))->toBeTrue();
    expect($customer->hasRole(Role::Customer->value))->toBeTrue();
});

it('roles are mutually exclusive by default', function (): void {
    $staff = User::factory()->staff()->create();

    expect($staff->hasRole(Role::Manager->value))->toBeFalse();
    expect($staff->hasRole(Role::Customer->value))->toBeFalse();
});
