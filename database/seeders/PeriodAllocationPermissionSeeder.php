<?php
// database/seeders/PeriodAllocationPermissionSeeder.php
//
// Creates the "Manage timetable constraints" permission — the one that
// guards the Period Allocation endpoints in TimetableController
// (getPeriodAllocationGrid, listPeriodAllocationSets,
// getPeriodAllocationSetDetail, savePeriodAllocationSet,
// deletePeriodAllocationSet). It existed already via
// TimetablePermissionTableSeeder but was never assigned to any role, so
// this just guarantees the permission row is there and ready to be
// assigned by hand from Roles & Permissions in the app. Idempotent — safe
// to run more than once. No role assignment here on purpose; assign it to
// whichever role(s) you choose from the UI.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PeriodAllocationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionName = 'Manage timetable constraints';

        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web',
        ]);

        $this->command->info("✓ Permission ready: {$permissionName}");
        $this->command->info('Assign it to your role(s) from Roles & Permissions in the app.');
    }
}
