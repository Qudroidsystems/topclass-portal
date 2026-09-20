<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableOverride extends Model
{
    use SoftDeletes;

    /**
     * Only the columns HolidayController::createHolidayOverrides() actually
     * mass-assigns via updateOrCreate(). The model had no $fillable/$guarded
     * at all, which Laravel treats as fully guarded — every field blocked —
     * so any mass assignment threw "Add [x] to fillable property".
     */
    protected $fillable = [
        'setting_id',
        'override_date',
        'override_type',
        'title',
        'description',
        'cancel_all_classes',
        'cancellation_reason',
        'status',
        'created_by',
    ];

    protected $casts = [
        'override_date'         => 'date',
        'cancel_all_classes'    => 'boolean',
        'notify_teachers'       => 'boolean',
        'notify_students'       => 'boolean',
        'notify_parents'        => 'boolean',
        'approved_at'           => 'datetime',
        'notification_sent_at'  => 'datetime',
        'modified_slots'        => 'array',
        'custom_schedule'       => 'array',
        'affected_periods'      => 'array',
    ];
}
