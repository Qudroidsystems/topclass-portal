<?php

namespace App\Models;

use App\Models\ParentRegistration;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Studentclass;
use App\Models\Studentpicture;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $table = 'studentRegistration';


    protected $fillable = [
        'userid',
        'title',
        'firstname',
        'lastname',
        'othername',
        'nationality',
        'gender',
        'phone_number',
        'future_ambition',
        'home_address2',
        'placeofbirth',
        'dateofbirth',
        'age',
        'religion',
        'state',
        'local',
        'last_school',
        'last_class',
        'registeredBy',
        'statusId',
        'batchid',
        'student_category',
        'student_status',
        'nin_number',
        'blood_group',
        'mother_tongue',
        'reason_for_leaving',
        'admissionNo',
        'admission_date',
        'admissionYear',
        'present_address',
        'permanent_address',
        'sport_house',
        'email',
        'city',
    ];

    protected $casts = [
        'dateofbirth' => 'date',
        'admission_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];



    // Add relationship to User
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'student_id', 'id');
    }

    // ... rest of your existing methods stay the same
    public function picture()
    {
        return $this->hasOne(Studentpicture::class, 'studentid', 'id');
    }

    public function schoolClass()
    {
        return $this->hasOne(Studentclass::class, 'studentId', 'id');
    }

    public function class()
    {
        return $this->hasOneThrough(
            Schoolclass::class,
            Studentclass::class,
            'studentId',
            'id',
            'id',
            'schoolclassid'
        );
    }

    public function term()
    {
        return $this->hasOneThrough(
            Schoolterm::class,
            Studentclass::class,
            'studentId',
            'id',
            'id',
            'termid'
        );
    }

    public function session()
    {
        return $this->hasOneThrough(
            Schoolsession::class,
            Studentclass::class,
            'studentId',
            'id',
            'id',
            'sessionid'
        );
    }

    public function parent()
    {
        return $this->hasOne(ParentRegistration::class, 'studentId', 'id');
    }

    // public function currentClass()
    // {
    //     return $this->hasOne(Studentclass::class, 'studentId', 'id')
    //         ->whereIn('sessionid', function ($query) {
    //             $query->select('id')
    //                   ->from('schoolsession')
    //                   ->where('status', 'Current')
    //                   ->orWhereRaw('id = (SELECT MAX(id) FROM schoolsession)');
    //         })
    //         ->with(['schoolclass.armRelation', 'term', 'session'])
    //         ->withDefault([
    //             'schoolclass' => ['schoolclass' => 'Not Assigned', 'armRelation' => null],
    //             'term' => ['term' => 'N/A'],
    //             'session' => ['session' => 'N/A']
    //         ]);
    // }

    public function classHistory()
    {
        return $this->hasMany(Studentclass::class, 'studentId', 'id')
            ->with(['schoolclass.armRelation', 'term', 'session', 'promotion'])
            ->orderByDesc('sessionid')
            ->orderByDesc('termid');
    }

    public function promotion()
    {
        return $this->hasOne(PromotionStatus::class, 'studentId', 'id')
            ->whereColumn('schoolclassid', 'studentclass.schoolclassid')
            ->whereColumn('sessionid', 'studentclass.sessionid')
            ->whereColumn('termid', 'studentclass.termid');
    }


     // ========== NEW: StudentCurrentTerm Relationships ==========

    /**
     * Get the current term record for this student
     */
    public function currentTerm()
    {
        return $this->hasOne(StudentCurrentTerm::class, 'studentId', 'id')
            ->where('is_current', true);
    }

    /**
     * Get all term records for this student
     */
    public function currentTerms()
    {
        return $this->hasMany(StudentCurrentTerm::class, 'studentId', 'id');
    }

    /**
     * Get current class through StudentCurrentTerm
     */
    public function currentClass()
    {
        return $this->hasOneThrough(
            Schoolclass::class,
            StudentCurrentTerm::class,
            'studentId',
            'id',
            'id',
            'schoolclassId'
        )->where('student_current_term.is_current', true);
    }

    /**
     * Get current term through StudentCurrentTerm
     */
    public function currentTermRelation()
    {
        return $this->hasOneThrough(
            Schoolterm::class,
            StudentCurrentTerm::class,
            'studentId',
            'id',
            'id',
            'termId'
        )->where('student_current_term.is_current', true);
    }

    /**
     * Get current session through StudentCurrentTerm
     */
    public function currentSession()
    {
        return $this->hasOneThrough(
            Schoolsession::class,
            StudentCurrentTerm::class,
            'studentId',
            'id',
            'id',
            'sessionId'
        )->where('student_current_term.is_current', true);
    }

    /**
     * Helper method to get formatted current term info
     */
    public function getCurrentTermInfo()
    {
        $currentTerm = $this->currentTerm;

        if (!$currentTerm) {
            return null;
        }

        // Eager load relationships if not already loaded
        if (!$currentTerm->relationLoaded('schoolClass')) {
            $currentTerm->load(['schoolClass.armRelation', 'term', 'session']);
        }

        return [
            'student_id' => $this->id,
            'current_class_id' => $currentTerm->schoolclassId,
            'current_class' => $currentTerm->schoolClass ? $currentTerm->schoolClass->schoolclass : null,
            'current_class_arm' => $currentTerm->schoolClass && $currentTerm->schoolClass->armRelation
                ? $currentTerm->schoolClass->armRelation->arm
                : null,
            'current_term_id' => $currentTerm->termId,
            'current_term' => $currentTerm->term ? $currentTerm->term->name : null,
            'current_session_id' => $currentTerm->sessionId,
            'current_session' => $currentTerm->session ? $currentTerm->session->name : null,
            'is_current' => $currentTerm->is_current
        ];
    }

    /**
     * Check if student has current term
     */
    public function hasCurrentTerm()
    {
        return $this->currentTerm()->exists();
    }

    /**
     * Scope to get students with current term
     */
    public function scopeWithCurrentTerm($query)
    {
        return $query->whereHas('currentTerm');
    }

    /**
     * Scope to get students by current class
     */
    public function scopeByCurrentClass($query, $classId)
    {
        return $query->whereHas('currentTerm', function($q) use ($classId) {
            $q->where('schoolclassId', $classId);
        });
    }

    /**
     * Scope to get students by current term
     */
    public function scopeByCurrentTerm($query, $termId)
    {
        return $query->whereHas('currentTerm', function($q) use ($termId) {
            $q->where('termId', $termId);
        });
    }

    /**
     * Scope to get students by current session
     */
    public function scopeByCurrentSession($query, $sessionId)
    {
        return $query->whereHas('currentTerm', function($q) use ($sessionId) {
            $q->where('sessionId', $sessionId);
        });
    }

    // ========== END NEW ==========
}
