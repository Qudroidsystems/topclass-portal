<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionStatus extends Model
{
    use HasFactory;

    protected $table = 'promotionStatus';

    // NOTE: no custom $primaryKey here on purpose. The table's real primary
    // key is the auto-increment `id` column from the original migration
    // ($table->id()) — the previous `protected $primaryKey = 'studentId'`
    // was wrong (studentId is not even unique per row: a student has one
    // promotionStatus row per class/session/term) and broke
    // updateOrCreate()/save() key resolution.

    protected $fillable = [
        'studentId',
        'schoolclassid',
        'position',
        'termid',
        'sessionid',
        'promotionStatus',
        'classstatus',
        'evaluated_at',
        'rule_applied',
        'overall_average',
        'promotion_pass_average',
    ];

    protected $casts = [
        'evaluated_at' => 'datetime',
    ];
}
