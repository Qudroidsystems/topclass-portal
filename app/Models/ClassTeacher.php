<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTeacher extends Model
{
    use HasFactory;

    protected $table = 'classteacher';

    protected $fillable = [
        'staffid',
        'schoolclassid',
        'termid',
        'sessionid',
    ];

    protected $casts = [
        'staffid'       => 'integer',
        'schoolclassid' => 'integer',
        'termid'        => 'integer',
        'sessionid'     => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'staffid');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid');
    }

    public function schoolterm()
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }

    public function schoolsession()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeForTeacher($q, $staffId)
    {
        return $q->where('staffid', (int) $staffId);
    }

    public function scopeForTerm($q, $termId)
    {
        return $q->where('termid', (int) $termId);
    }

    public function scopeForSession($q, $sessionId)
    {
        return $q->where('sessionid', (int) $sessionId);
    }

    public function scopeForClass($q, $classId)
    {
        return $q->where('schoolclassid', (int) $classId);
    }
}
