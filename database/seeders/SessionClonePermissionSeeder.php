<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions for "Clone Session / Term Setup".
 *
 *   php artisan db:seed --class=SessionClonePermissionSeeder
 */
class SessionClonePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View session clone',   // open the page, preview, view/download snapshots
            'Run session clone',    // execute a clone
            'Revert session clone', // undo a clone from its snapshot
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Session Clone']
            );
        }

        // Super Admin always gets everything
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
