<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Offline CBT: lets a school run CBT exams on a local Wi-Fi network (Flutter
 * desktop app) and upload the attempts afterwards.
 *
 *  offline_cbt_devices   – admin computers allowed to download exams / upload results (API key)
 *  offline_cbt_pins      – per-student exam PINs for offline login
 *  offline_cbt_packages  – every exam package downloaded (frozen questions + checksum)
 *  offline_cbt_syncs     – every uploaded attempt, keyed by its UUID (makes sync idempotent)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('offline_cbt_devices')) {
            Schema::create('offline_cbt_devices', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('token_hash', 64)->unique();
                $t->string('token_hint', 12);                 // first characters, to recognise a key
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamp('last_seen_at')->nullable();
                $t->string('last_ip', 64)->nullable();
                $t->string('app_version', 32)->nullable();
                $t->timestamp('revoked_at')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('offline_cbt_pins')) {
            Schema::create('offline_cbt_pins', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->unique();  // users.id of the student
                $t->string('pin', 12);
                $t->unsignedBigInteger('generated_by')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('offline_cbt_packages')) {
            Schema::create('offline_cbt_packages', function (Blueprint $t) {
                $t->id();
                $t->uuid('package_uuid')->unique();
                $t->unsignedBigInteger('device_id');
                $t->json('exam_ids');
                $t->string('checksum', 64);
                $t->unsignedInteger('student_count')->default(0);
                $t->timestamps();
                $t->index('device_id');
            });
        }

        if (!Schema::hasTable('offline_cbt_syncs')) {
            Schema::create('offline_cbt_syncs', function (Blueprint $t) {
                $t->id();
                $t->uuid('attempt_uuid')->unique();
                $t->unsignedBigInteger('device_id');
                $t->uuid('package_uuid')->nullable();
                $t->unsignedBigInteger('exam_id');
                $t->unsignedBigInteger('user_id');
                // synced | conflict | conflict_accepted | conflict_discarded | rejected
                $t->string('status', 24);
                $t->string('message', 500)->nullable();
                $t->unsignedBigInteger('exam_attempt_id')->nullable();
                $t->timestamp('started_at')->nullable();
                $t->timestamp('submitted_at')->nullable();
                $t->unsignedInteger('duration_seconds')->nullable();
                $t->string('workstation', 100)->nullable();   // which student computer
                $t->decimal('score', 8, 2)->nullable();
                $t->decimal('total_marks', 8, 2)->nullable();
                $t->unsignedInteger('attempted')->nullable();
                $t->json('answers');                          // [{question_id, answer}]
                $t->boolean('outside_window')->default(false);
                $t->unsignedBigInteger('resolved_by')->nullable();
                $t->timestamp('resolved_at')->nullable();
                $t->timestamps();
                $t->index(['exam_id', 'user_id']);
                $t->index('status');
            });
        }

        if (Schema::hasTable('permissions')) {
            $now = now();
            $perm = DB::table('permissions')->where('name', 'Manage offline-cbt')->first();
            $permId = $perm->id ?? DB::table('permissions')->insertGetId([
                'name' => 'Manage offline-cbt', 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now,
            ]);
            if (Schema::hasColumn('permissions', 'title')) {
                DB::table('permissions')->where('id', $permId)->update(['title' => 'Offline CBT Management']);
            }
            if (Schema::hasTable('roles') && Schema::hasTable('role_has_permissions')) {
                $roleIds = DB::table('roles')->whereIn('name', ['Super Admin', 'Admin', 'Administrator', 'super-admin', 'admin'])->pluck('id');
                foreach ($roleIds as $rid) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permId, 'role_id' => $rid]);
                }
            }
            try { app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions(); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_cbt_syncs');
        Schema::dropIfExists('offline_cbt_packages');
        Schema::dropIfExists('offline_cbt_pins');
        Schema::dropIfExists('offline_cbt_devices');
    }
};
