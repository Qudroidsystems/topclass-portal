<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classcategory extends Model
{
    use HasFactory;

    protected $table = 'classcategories';

    protected $fillable = [
        'category',
        'ca1score',
        'ca2score',
        'ca3score',
        'examscore',
        'is_senior',
    ];

    protected $casts = [
        'is_senior' => 'boolean',
        'ca1score'  => 'float',
        'ca2score'  => 'float',
        'ca3score'  => 'float',
        'examscore' => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * Project 1: schoolclass.classcategoryid is a direct FK.
     * Returns all schoolclasses that point at this category.
     */
    public function schoolclasses()
    {
        return $this->hasMany(Schoolclass::class, 'classcategoryid');
    }

    /**
     * Alias used by some P2 code paths — kept for compatibility if you ever
     * add the pivot table. For P1, just delegates to schoolclasses().
     */
    // public function schoolClasses()
    // {
    //     return $this->schoolclasses();
    // }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'classcategory_id');
    }

    // ── Grade calculation ────────────────────────────────────────────────────

    public function calculateGrade($totalScore)
    {
        return $this->is_senior
            ? $this->calculateSeniorGrade($totalScore)
            : $this->calculateJuniorGrade($totalScore);
    }

    private function calculateJuniorGrade($totalScore)
    {
        if ($totalScore >= 70 && $totalScore <= 100) return 'A';
        if ($totalScore >= 60) return 'B';
        if ($totalScore >= 50) return 'C';
        if ($totalScore >= 40) return 'D';
        return 'F';
    }

    private function calculateSeniorGrade($totalScore)
    {
        if ($totalScore >= 75 && $totalScore <= 100) return 'A1';
        if ($totalScore >= 70) return 'B2';
        if ($totalScore >= 65) return 'B3';
        if ($totalScore >= 60) return 'C4';
        if ($totalScore >= 55) return 'C5';
        if ($totalScore >= 50) return 'C6';
        if ($totalScore >= 45) return 'D7';
        if ($totalScore >= 40) return 'E8';
        return 'F9';
    }

    // ── Accessors (used by the new blade) ────────────────────────────────────

    public function getGradeScaleAttribute(): array
    {
        return $this->is_senior
            ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9']
            : ['A', 'B', 'C', 'D', 'F'];
    }

    public function getPassingGradesAttribute(): array
    {
        return $this->is_senior
            ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8']
            : ['A', 'B', 'C', 'D'];
    }

    public function getGradeTypeAttribute(): string
    {
        return $this->is_senior ? 'Senior' : 'Junior';
    }

    /**
     * Sum of all CA maxes + exam max. Used by the blade's "Total Max" column.
     * (In P1's model this is a computed sum, not a stored column.)
     */
    public function getTotalMaxScoreAttribute(): float
    {
        return (float) $this->ca1score
             + (float) $this->ca2score
             + (float) $this->ca3score
             + (float) $this->examscore;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSenior($query)
    {
        return $query->where('is_senior', true);
    }

    public function scopeJunior($query)
    {
        return $query->where('is_senior', false);
    }
}
