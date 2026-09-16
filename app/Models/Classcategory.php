<?php
// app/Models/Classcategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classcategory extends Model
{
    use HasFactory;

    protected $table = "classcategories";

    protected $fillable = [
        'category',
        'ca1score',
        'ca2score',
        'ca3score',
        'examscore',
        'is_senior',
        'promotion_pass_average',
    ];

    protected $casts = [
        'is_senior'              => 'boolean',
        'promotion_pass_average' => 'decimal:2',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Classes that belong to this category.
     * FK: schoolclass.classcategoryid → classcategories.id
     */
    public function schoolclasses()
    {
        return $this->hasMany(Schoolclass::class, 'classcategoryid');
    }

    /**
     * Alias — same hasMany, camelCase spelling.
     */
    // public function schoolClasses()
    // {
    //     return $this->schoolclasses();
    // }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'classcategory_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'classcategory_id');
    }

    // =========================================================================
    // GRADE CALCULATION
    // =========================================================================

    /**
     * Calculate grade based on total score and class type.
     */
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

    // =========================================================================
    // HELPERS / SCOPES
    // =========================================================================

    public function getGradeTypeAttribute(): string
    {
        return $this->is_senior ? 'Senior' : 'Junior';
    }

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

    public function scopeSenior($query)
    {
        return $query->where('is_senior', true);
    }

    public function scopeJunior($query)
    {
        return $query->where('is_senior', false);
    }
}