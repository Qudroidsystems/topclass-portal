<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTeacher extends Model
{
    use HasFactory;

    protected $table = 'subjectteacher';

    protected $fillable = [
        'userid',
        'staffid',
        'subjectid',
        'termid',
        'sessionid',
    ];

    protected $casts = [
        'staffid'   => 'integer',
        'subjectid' => 'integer',
        'termid'    => 'integer',
        'sessionid' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function schoolsession()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }

    public function schoolterm()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'staffid', 'id');
    }

    public function subjectclass()
    {
        return $this->hasOne(Subjectclass::class, 'subjectteacherid', 'id');
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
}
