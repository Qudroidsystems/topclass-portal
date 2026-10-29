<?php
// app/Models/SchoolBillTermSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolBillTermSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_bill_class_term_session';

    protected $fillable = [
        'bill_id',
        'class_id',
        'termid_id',
        'session_id',
        'student_status_id',
        'created_by',
        'is_active',
        'display_order',
        'is_required',
    ];

    protected $casts = [
        'bill_id'       => 'integer',
        'class_id'      => 'integer',
        'termid_id'     => 'integer',
        'session_id'    => 'integer',
        'student_status_id' => 'integer',
        'created_by'    => 'integer',
        'is_active'     => 'boolean',
        'is_required'   => 'boolean',
        'display_order' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function schoolBill()
    {
        return $this->belongsTo(SchoolBillModel::class, 'bill_id');
    }

    /**
     * Note: 'class' is a reserved keyword in PHP — use schoolClass() instead.
     */
    public function schoolClass()
    {
        return $this->belongsTo(Schoolclass::class, 'class_id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('termid_id', $termId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }


    // ── Old / New student targeting ────────────────────────────────────

    /**
     * SQL expression for "which students does this assignment apply to".
     * The assignment's own student_status_id wins; when it is NULL (older rows)
     * the bill's own statusId is used. 0 / NULL / '' means both old and new.
     * Requires school_bill to be joined in the query.
     */
    public static function effectiveStatusExpr(
        string $assignTable = 'school_bill_class_term_session',
        string $billTable = 'school_bill'
    ): string {
        return "COALESCE({$assignTable}.student_status_id, {$billTable}.statusId)";
    }

    /**
     * Restrict a query (Eloquent or DB::table) to the assignments that apply to a
     * student of the given status (student_status.id: 1 = old, 2 = new).
     */
    public static function applyStudentStatusFilter(
        $query,
        $studentStatusId,
        string $assignTable = 'school_bill_class_term_session',
        string $billTable = 'school_bill'
    ) {
        $eff = self::effectiveStatusExpr($assignTable, $billTable);

        return $query->where(function ($q) use ($eff, $studentStatusId) {
            $q->whereRaw("{$eff} IS NULL")
              ->orWhereRaw("{$eff} = ''")
              ->orWhereRaw("{$eff} = 0")
              ->orWhereRaw("{$eff} = ?", [(int) $studentStatusId]);
        });
    }

    public static function audienceLabel($statusId): ?string
    {
        if ($statusId === null || $statusId === '') return null;
        return match ((int) $statusId) {
            1       => 'Old students',
            2       => 'New students',
            default => 'Old & new students',
        };
    }

    // ── Static helpers ─────────────────────────────────────────────────

    /**
     * Get all active bill assignments for a student based on their current class.
     */
    public static function getBillsForStudent($studentId, $termId, $sessionId)
    {
        $student = \App\Models\Student::find($studentId);

        if (!$student || !$student->currentClass()) {
            return collect([]);
        }

        $classId = $student->currentClass()->schoolclassid;

        return self::where('class_id', $classId)
            ->where('termid_id', $termId)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->with('schoolBill')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Check whether a specific bill/class/term/session combination already exists
     * (excluding soft-deleted records).
     */
    public static function isBillAssigned($billId, $classId, $termId, $sessionId): bool
    {
        return self::where('bill_id', $billId)
            ->where('class_id', $classId)
            ->where('termid_id', $termId)
            ->where('session_id', $sessionId)
            ->exists();
    }

    /**
     * Get all unique classes that have been assigned this bill.
     */
    public function getAssignedClasses()
    {
        return Schoolclass::whereIn('id',
            self::where('bill_id', $this->bill_id)->pluck('class_id')
        )->get();
    }
}
