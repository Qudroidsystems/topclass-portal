<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yearbooks (one per graduating set) and the alumni network.
 *
 * yearbooks          – a graduating set: cover, class photo, principal's message, visibility.
 * yearbook_entries   – one graduating student in a yearbook: graduation photo (gown / suit),
 *                      profile, testimonial, awards. Students or parents can send a photo and
 *                      details through a private link; it waits (pending_*) for admin approval.
 * alumni             – old students in the alumni network, from a yearbook or the website's
 *                      "Join" form (approved by the admin before they are listed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('yearbooks')) {
            Schema::create('yearbooks', function (Blueprint $t) {
                $t->id();
                $t->string('title', 150);
                $t->string('slug', 160)->unique();
                $t->unsignedSmallInteger('graduation_year')->index();
                $t->unsignedBigInteger('session_id')->nullable();
                $t->string('class_label', 100)->nullable();        // e.g. "SS 3" / "Primary 6"
                $t->string('theme', 200)->nullable();              // set motto / theme
                $t->text('description')->nullable();
                $t->longText('principal_message')->nullable();
                $t->string('principal_name', 150)->nullable();
                $t->string('principal_photo')->nullable();
                $t->string('cover')->nullable();
                $t->string('class_photo')->nullable();
                $t->unsignedBigInteger('album_id')->nullable();    // website gallery album of the set
                $t->string('visibility', 10)->default('public');    // public | portal
                $t->boolean('allow_submissions')->default(true);
                $t->date('submissions_close_on')->nullable();
                $t->boolean('published')->default(false);
                $t->unsignedSmallInteger('sort')->default(0);
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('yearbook_entries')) {
            Schema::create('yearbook_entries', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('yearbook_id')->index();
                $t->unsignedBigInteger('student_id')->nullable()->index();
                $t->string('name', 150);
                $t->string('nickname', 80)->nullable();
                $t->string('admission_no', 60)->nullable();
                $t->string('gender', 10)->nullable();
                $t->string('class_name', 60)->nullable();          // e.g. "SS 3 A"
                $t->string('house', 60)->nullable();
                $t->string('position', 100)->nullable();           // Head Girl, Labour Prefect, Class Captain…
                $t->string('awards', 300)->nullable();             // comma separated: Best Graduating Student, Most Likely to…
                $t->string('photo')->nullable();                   // graduation photo (gown / suit / attire)
                $t->string('portal_photo')->nullable();            // copy of the portal passport (fallback)
                $t->string('ambition', 150)->nullable();
                $t->string('hobbies', 200)->nullable();
                $t->string('favourite_subject', 100)->nullable();
                $t->string('quote', 300)->nullable();              // favourite quote / motto
                $t->text('best_memory')->nullable();
                $t->text('testimonial')->nullable();               // the student's words about the school
                $t->text('school_remark')->nullable();             // principal's / teacher's testimonial
                $t->string('next_step', 150)->nullable();          // university / course / plans
                $t->boolean('featured')->default(false);
                $t->boolean('published')->default(true);
                $t->unsignedSmallInteger('sort')->default(0);
                $t->string('upload_token', 64)->nullable()->unique();
                $t->timestamp('link_sent_at')->nullable();
                $t->json('pending')->nullable();                   // details sent through the upload link
                $t->string('pending_photo')->nullable();
                $t->timestamp('pending_at')->nullable();
                $t->string('pending_by', 150)->nullable();         // "Mrs Ade (Parent)"
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('alumni')) {
            Schema::create('alumni', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('yearbook_entry_id')->nullable()->index();
                $t->unsignedBigInteger('student_id')->nullable()->index();
                $t->string('name', 150);
                $t->string('maiden_name', 100)->nullable();
                $t->unsignedSmallInteger('graduation_year')->nullable()->index();
                $t->string('class_set', 100)->nullable();
                $t->string('photo')->nullable();
                $t->string('occupation', 150)->nullable();
                $t->string('organisation', 150)->nullable();
                $t->string('industry', 80)->nullable()->index();
                $t->string('location', 150)->nullable();
                $t->string('higher_institution', 200)->nullable();
                $t->text('bio')->nullable();
                $t->text('message')->nullable();                   // message to current students
                $t->string('email', 150)->nullable();
                $t->string('phone', 40)->nullable();
                $t->string('linkedin', 200)->nullable();
                $t->boolean('show_contact')->default(false);
                $t->boolean('mentor')->default(false);             // willing to mentor students
                $t->string('status', 12)->default('pending')->index(); // pending | approved | rejected
                $t->boolean('featured')->default(false);
                $t->string('edit_token', 64)->nullable()->unique();
                $t->json('pending')->nullable();                   // profile changes waiting for approval
                $t->string('pending_photo')->nullable();
                $t->timestamp('pending_at')->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamps();
            });
        }

        // Permission for the Yearbook & Alumni menu: admin roles and whoever manages the website.
        if (Schema::hasTable('permissions')) {
            $now = now();
            $id = DB::table('permissions')->where('name', 'Manage yearbook')->where('guard_name', 'web')->value('id');
            if (!$id) {
                $row = ['name' => 'Manage yearbook', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now];
                if (Schema::hasColumn('permissions', 'title')) $row['title'] = 'Yearbook & Alumni';
                $id = DB::table('permissions')->insertGetId($row);
            }
            $roles = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator', 'super-admin', 'admin'])->pluck('id');
            $web = DB::table('permissions')->where('name', 'Manage website')->value('id');
            if ($web && Schema::hasTable('role_has_permissions')) {
                $roles = $roles->merge(DB::table('role_has_permissions')->where('permission_id', $web)->pluck('role_id'));
            }
            foreach ($roles->unique() as $rid) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $rid]);
            }
            try { app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
        Schema::dropIfExists('yearbook_entries');
        Schema::dropIfExists('yearbooks');
    }
};
