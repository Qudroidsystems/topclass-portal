<?php
// app/Models/Schoolclass.php

namespace App\Models;

use App\Models\Schoolarm;
use App\Models\Classcategory;
use App\Models\Student;
use App\Models\StudentCurrentTerm;
use App\Models\Subjectclass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Schoolclass extends Model
{
    use HasFactory;

    protected $table = "schoolclass";

    protected $fillable = [
        'schoolclass',
        'arm',
        'classcategoryid',
        'description',
    ];

    // =========================================================================
    // CLASS CATEGORY — SINGLE (belongsTo via classcategoryid)
    // =========================================================================

    /**
     * The single class category this class belongs to.
     * Column: schoolclass.classcategoryid  →  classcategories.id
     */
    public function classcategory()
    {
        return $this->belongsTo(Classcategory::class, 'classcategoryid', 'id');
    }

    /**
     * Alias — same belongsTo as classcategory().
     * Kept so any code calling ->classcategories (plural) still works,
     * but returns a single model (or null), not a Collection.
     */
    public function classcategories()
    {
        return $this->classcategory();
    }

    // =========================================================================
    // ARMS
    // =========================================================================

    public function armRelation()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    public function arm()
    {
        return $this->belongsTo(Schoolarm::class, 'arm');
    }

    public function arms()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    // =========================================================================
    // SUBJECT CLASSES
    // =========================================================================

    public function subjectClasses()
    {
        return $this->hasMany(Subjectclass::class, 'schoolclassid', 'id');
    }

    // =========================================================================
    // CURRENT STUDENTS (through StudentCurrentTerm)
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
            'schoolclassId',    // FK on student_current_term → schoolclass.id
            'id',               // FK on student → student_current_term.studentId
            'id',               // local key on schoolclass
            'studentId'         // local key on student_current_term
        )->where('student_current_term.is_current', true);
    }

    // =========================================================================
    // PROMOTION PASS AVERAGE (via pivot-free classcategoryid)
    // =========================================================================

    /**
     * Read promotion_pass_average from classcategories table
     * (via the FK classcategoryid).
     *
     * If your schema stores this in a pivot table (schoolclass_classcategory),
     * the fallback below will cover that too.
     */
    public function getPromotionPassAverageAttribute()
    {
        // Primary: from the classcategory we belong to
        if ($this->classcategoryid) {
            $fromCategory = DB::table('classcategories')
                ->where('id', $this->classcategoryid)
                ->value('promotion_pass_average');

            if ($fromCategory !== null) {
                return $fromCategory;
            }
        }

        // Fallback: legacy pivot table (if it exists)
        try {
            $pivot = DB::table('schoolclass_classcategory')
                ->where('schoolclass_id', $this->id)
                ->value('promotion_pass_average');

            return $pivot;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function setPromotionPassAverageAttribute($value)
    {
        // Primary: update classcategories
        if ($this->classcategoryid) {
            $updated = DB::table('classcategories')
                ->where('id', $this->classcategoryid)
                ->update(['promotion_pass_average' => $value]);

            if ($updated) {
                return;
            }
        }

        // Fallback: legacy pivot table
        try {
            DB::table('schoolclass_classcategory')
                ->where('schoolclass_id', $this->id)
                ->update(['promotion_pass_average' => $value]);
        } catch (\Throwable $e) {
            // silently ignore
        }
    }

    // =========================================================================
    // CONVENIENCE ACCESSORS
    // =========================================================================

    public function getIsSeniorAttribute(): bool
    {
        $cat = $this->classcategory;
        return $cat ? (bool) $cat->is_senior : false;
    }
}