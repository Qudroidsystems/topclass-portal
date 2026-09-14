<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subjectclass extends Model
{
    use HasFactory;

    protected $table = 'subjectclass';

    protected $fillable = [
        'schoolclassid',
        'subjectid',
        'subjectteacherid',
        'termid',
        'sessionid',
    ];

    protected $casts = [
        'schoolclassid'    => 'integer',
        'subjectid'        => 'integer',
        'subjectteacherid' => 'integer',
        'termid'           => 'integer',
        'sessionid'        => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function subjectTeacher()
    {
        return $this->belongsTo(SubjectTeacher::class, 'subjectteacherid', 'id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjectid', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staffid', 'id');
    }

    public function broadsheets()
    {
        return $this->hasMany(Broadsheets::class, 'subjectclass_id', 'id');
    }

    public function broadsheetsMock()
    {
        return $this->hasMany(BroadsheetsMock::class, 'subjectclass_id', 'id');
    }

    public function registrationStatus()
    {
        return $this->hasMany(SubjectRegistrationStatus::class, 'subjectclassid', 'id');
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getFullClassNameAttribute()
    {
        $arm = $this->schoolClass?->armRelation?->arm ?? '';
        return trim(($this->schoolClass?->schoolclass ?? '') . ' ' . $arm);
    }

    public function getSubjectDisplayAttribute()
    {
        return ($this->subject?->subject ?? '') . ' (' . ($this->subject?->subject_code ?? '') . ')';
    }
}
