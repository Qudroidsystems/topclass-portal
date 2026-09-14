<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Schoolclass extends Model
{
    use HasFactory;

    protected $table = 'schoolclass';

    protected $fillable = [
        'schoolclass',
        'arm',
        'classcategoryid',
        'description',
    ];

    protected $casts = [
        'arm'             => 'integer',
        'classcategoryid' => 'integer',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /** Single category via the FK (canonical for Project 1). */
    public function classcategory()
    {
        return $this->belongsTo(Classcategory::class, 'classcategoryid', 'id');
    }

    /** Pivot-based plural, kept for compatibility with any P2-style code. */
    public function classcategories()
    {
        return $this->belongsToMany(
            Classcategory::class,
            'schoolclass_classcategory',
            'schoolclass_id',
            'classcategory_id'
        )->withPivot('promotion_pass_average')
         ->withTimestamps();
    }

    public function arm()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    public function armRelation()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    public function subjectClasses()
    {
        return $this->hasMany(Subjectclass::class, 'schoolclassid', 'id');
    }

    // =========================================================================
    // PROMOTION PASS AVERAGE (pivot-backed)
    // =========================================================================

    public function getPromotionPassAverageAttribute()
    {
        if (!\Schema::hasTable('schoolclass_classcategory')) return null;

        $pivot = DB::table('schoolclass_classcategory')
            ->where('schoolclass_id', $this->id)
            ->first();

        return $pivot ? $pivot->promotion_pass_average : null;
    }

    public function setPromotionPassAverageAttribute($value)
    {
        if (!\Schema::hasTable('schoolclass_classcategory')) return;

        $exists = DB::table('schoolclass_classcategory')
            ->where('schoolclass_id', $this->id)
            ->exists();

        if ($exists) {
            DB::table('schoolclass_classcategory')
                ->where('schoolclass_id', $this->id)
                ->update([
                    'promotion_pass_average' => $value,
                    'updated_at'             => now(),
                ]);
        } elseif (!empty($this->classcategoryid)) {
            DB::table('schoolclass_classcategory')->insert([
                'schoolclass_id'         => $this->id,
                'classcategory_id'       => $this->classcategoryid,
                'promotion_pass_average' => $value,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }
    }

    // =========================================================================
    // CURRENT STUDENTS (only if student_current_term exists)
    // =========================================================================

    public function studentCurrentTerms()
    {
        return $this->hasMany(StudentCurrentTerm::class, 'schoolclassId', 'id');
    }

    public function currentStudents()
    {
        return $this->hasManyThrough(
            Student::class,
            StudentCurrentTerm::class,
            'schoolclassId',
            'id',
            'id',
            'studentId'
        )->where('student_current_term.is_current', true);
    }
}
