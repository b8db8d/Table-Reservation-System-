<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $staffPermissions = [
            Permission::ReservationsViewAny->value,
            Permission::ReservationsView->value,
            Permission::ReservationsCreate->value,
            Permission::ReservationsUpdate->value,
            Permission::ReservationsConfirm->value,
            Permission::ReservationsReject->value,
        ];

        RoleModel::findOrCreate(Role::Customer->value, 'web')
            ->syncPermissions([Permission::ReservationsView->value]);

        RoleModel::findOrCreate(Role::Staff->value, 'web')
            ->syncPermissions($staffPermissions);

        RoleModel::findOrCreate(Role::Manager->value, 'web')
            ->syncPermissions(PermissionModel::all());
    }
}
