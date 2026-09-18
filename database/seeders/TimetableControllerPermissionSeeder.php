<?php
// database/seeders/TimetableControllerPermissionSeeder.php
//
// Every permission name referenced by TimetableController's middleware()
// calls, created and assigned in one pass (same pattern as
// ClubPermissionSeeder / PeriodAllocationPermissionSeeder). Safe to re-run.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TimetableControllerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // All permissions used across TimetableController's middleware()
        $timetableControllerPermissions = [
            'View timetable',
            'Create timetable',
            'Edit timetable',
            'Delete timetable',
            'Generate timetable',
            'View my timetable',
            'Manage timetable settings',
            'Manage timetable constraints',
            'View timetable reports',
            'Export timetable',
            'Request substitute',
            'Approve substitute',
            'View substitute requests',
            'Manage teacher availability',
            'Check timetable conflicts',
            'Send timetable notifications',
            'Publish timetable',
        ];

        // Create every permission
        foreach ($timetableControllerPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Assign all of them to admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($timetableControllerPermissions);

        // Also assign to super-admin if exists
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        if ($superAdminRole->exists) {
            $superAdminRole->givePermissionTo($timetableControllerPermissions);
        }

        $this->command->info('✅ All TimetableController permissions seeded successfully.');
    }
}
