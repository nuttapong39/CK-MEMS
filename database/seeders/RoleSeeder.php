<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'equipment.view', 'equipment.create', 'equipment.update', 'equipment.delete',
            'calibration.view', 'calibration.create',
            'maintenance.view', 'maintenance.create',
            'repair.view', 'repair.view_all', 'repair.create', 'repair.update_status',
            'qrcode.manage',
            'mophalert.manage',
            'user.manage',
            'report.export',
            'activitylog.view',
            'dashboard.view',
        ];

        foreach (['web', 'api'] as $guard) {
            foreach ($permissions as $perm) {
                Permission::findOrCreate($perm, $guard);
            }

            $admin = Role::findOrCreate('admin', $guard);
            $admin->syncPermissions(Permission::where('guard_name', $guard)->get());

            $staff = Role::findOrCreate('staff', $guard);
            $staff->syncPermissions(
                Permission::where('guard_name', $guard)
                    ->whereIn('name', [
                        'equipment.view', 'equipment.create', 'equipment.update',
                        'calibration.view', 'calibration.create',
                        'maintenance.view', 'maintenance.create',
                        'repair.view', 'repair.view_all', 'repair.create', 'repair.update_status',
                        'report.export', 'dashboard.view',
                    ])->get()
            );

            $user = Role::findOrCreate('user', $guard);
            $user->syncPermissions(
                Permission::where('guard_name', $guard)
                    ->whereIn('name', ['repair.view', 'repair.create', 'dashboard.view'])->get()
            );
        }

        $this->command->info('Roles & permissions seeded: admin, staff, user');
    }
}
