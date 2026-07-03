<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleTableSeeder;
use Database\Seeders\TermTableSeeder;
use Database\Seeders\UserTableSeeder;
use Database\Seeders\PermissionTableSeeder;
use Database\Seeders\StudentStatusTableSeeder;
use Database\Seeders\StudentPermissionTableSeeder;
use Database\Seeders\ViewClassPermissionTableSeeder;
use Database\Seeders\StudentReportPermissionTableSeeder;
use Database\Seeders\SubjectVettedPermissionTableSeeder;
use Database\Seeders\SubjectVettingsPermissionTableSeeder;
use Database\Seeders\MyClassMySubjectPermissionTableSeeder;
use Database\Seeders\MySubjectVettingsPermissionTableSeeder;
use Database\Seeders\PrincipalscommentPermissionTableSeeder;
use Database\Seeders\SchoolInformationPermissionTableSeeder;
use Database\Seeders\StudentMockReportPermissionTableSeeder;
use Database\Seeders\CompulsorySubjectsPermissionTableSeeder;
use Database\Seeders\MockSubjectVettingsPermissionTableSeeder;
use Database\Seeders\MyMockSubjectVettingsPermissionTableSeeder;
use Database\Seeders\SchoolBillTermSessionPermissionTableSeeder;
use Database\Seeders\SubjectUploadForStaffPermissionTableSeeder;
use Database\Seeders\SubjectClassResultRoomOperationPermissionTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            PermissionTableSeeder::class,
            // RoleTableSeeder::class,
            UserTableSeeder::class,
            TermTableSeeder::class,

            ViewClassPermissionTableSeeder::class,
            CompulsorySubjectsPermissionTableSeeder::class,
            MockSubjectVettingsPermissionTableSeeder::class,
            MyClassMySubjectPermissionTableSeeder::class,
            MyMockSubjectVettingsPermissionTableSeeder::class,
            MySubjectVettingsPermissionTableSeeder::class,
            PrincipalscommentPermissionTableSeeder::class,
            SchoolBillTermSessionPermissionTableSeeder::class,
            SchoolInformationPermissionTableSeeder::class,
            StudentMockReportPermissionTableSeeder::class,
            StudentPermissionTableSeeder::class,
            StudentReportPermissionTableSeeder::class,
            StudentStatusTableSeeder::class,
            SubjectClassResultRoomOperationPermissionTableSeeder::class,
            SubjectUploadForStaffPermissionTableSeeder::class,
            SubjectVettedPermissionTableSeeder::class,
            SubjectVettingsPermissionTableSeeder::class,
            PromotionPermissionTableSeeder::class,  // Add this line
        
         

           
            // Add more seeders as needed
        ]);
    }
}
