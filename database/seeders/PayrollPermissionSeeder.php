<?php
// database/seeders/PayrollPermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PayrollPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View payroll',
            'Process payroll',
            'Approve payroll',
            'View payslip',
            'Download payslip',
            'Manage salary structures',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['title' => 'Payroll Management']
            );
        }

        $this->command->info('✅ Payroll permissions seeded successfully!');
    }
}
