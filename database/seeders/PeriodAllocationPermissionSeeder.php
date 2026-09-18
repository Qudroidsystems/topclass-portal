<?php
// database/seeders/PeriodAllocationPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PeriodAllocationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Period Allocation Permission
        $periodAllocationPermissions = [
            'Manage timetable constraints',
        ];

        // Create period allocation permission
        foreach ($periodAllocationPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Assign period allocation permission to admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($periodAllocationPermissions);

        // Also assign to super-admin if exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        if ($superAdminRole->exists) {
            $superAdminRole->givePermissionTo($periodAllocationPermissions);
        }

        $this->command->info('✅ Period allocation permissions seeded successfully.');
    }
}
