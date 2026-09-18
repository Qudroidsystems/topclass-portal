<?php
// database/seeders/PeriodAllocationPermissionSeeder.php
//
// The "Manage timetable constraints" permission (used to guard the Period
// Allocation endpoints in TimetableController) was created by
// TimetablePermissionTableSeeder but was never actually assigned to any
// role — no seeder ever granted it, and this app has no "Super Admin
// bypasses everything" gate, so every role — including admin — was being
// rejected by Spatie's permission middleware with no entry in
// storage/logs/laravel.log (Laravel doesn't log ordinary 403s by default).
//
// This seeder is idempotent and safe to re-run:
//   1. Makes sure the permission row exists.
//   2. Grants it to the conventional 'admin' / 'super-admin' roles used by
//      the other feature seeders in this codebase (see ClubPermissionSeeder,
//      SportPermissionSeeder), creating those roles if they don't exist yet.
//   3. Also grants it to legacy-named 'Super Admin' / 'Admin' roles
//      (created by RoleTableSeeder / UserTableSeeder) IF they already
//      exist, without creating duplicates.
//   4. As a safety net, grants it to every role that already holds
//      'Edit timetable' or 'Manage timetable settings' — i.e. whoever can
//      already manage timetables today gets Period Allocation too,
//      regardless of which role name your account actually has.

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PeriodAllocationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionName = 'Manage timetable constraints';

        $permission = Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web',
        ]);
        $this->command->info("✓ Permission exists: {$permissionName}");

        $grantedTo = [];

        // 1) Conventional lowercase role names used by sibling seeders.
        foreach (['admin', 'super-admin'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                $grantedTo[] = $roleName;
            }
        }

        // 2) Legacy-named roles — only touch them if they already exist,
        //    never create new ones with this casing.
        foreach (['Super Admin', 'Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                $grantedTo[] = $roleName;
            }
        }

        // 3) Safety net: any role that can already manage timetables
        //    (under whatever name it has) gets Period Allocation too.
        $relatedPermissionNames = ['Edit timetable', 'Manage timetable settings'];
        $rolesWithTimetableAccess = Role::whereHas('permissions', function ($q) use ($relatedPermissionNames) {
            $q->whereIn('name', $relatedPermissionNames)->where('guard_name', 'web');
        })->get();

        foreach ($rolesWithTimetableAccess as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                $grantedTo[] = $role->name . ' (via existing timetable access)';
            }
        }

        if (empty($grantedTo)) {
            $this->command->info("ℹ️  No changes — every relevant role already had \"{$permissionName}\".");
        } else {
            $this->command->info('✅ Granted "' . $permissionName . '" to: ' . implode(', ', array_unique($grantedTo)));
        }
    }
}
