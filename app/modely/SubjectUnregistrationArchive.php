<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectUnregistrationArchive extends Model
{
    protected $table = 'subject_unregistration_archive';

    protected $fillable = [
        'studentid',
        'subjectclassid',
        'staffid',
        'termid',
        'sessionid',
        'subjectid',
        'schoolclassid',
        'broadsheet_record_id',
        'unregistered_by',
        'snapshot_name',
        'snapshot_notes',
        'status',
        'unregistered_at',
        'actioned_at',
    ];

    protected $casts = [
        'unregistered_at' => 'datetime',
        'actioned_at'     => 'datetime',
    ];

    // ── Status constants ─────────────────────────────────────────────────────
    const STATUS_ARCHIVED            = 'archived';
    const STATUS_RESTORED            = 'restored';
    const STATUS_PERMANENTLY_DELETED = 'permanently_deleted';

    // ── Relationships ────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studentid');
    }

    public function subjectclass(): BelongsTo
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclassid');
    }

    /**
     * The teacher assigned to this subject at the time of unregistration.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staffid');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Schoolterm::class, 'termid');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid');
    }

    /**
     * The staff member who performed the unregistration action.
     */
    public function unregisteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unregistered_by');
    }

    /**
     * Score snapshots captured at the moment of unregistration.
     * Deleted automatically (CASCADE) when this archive row is hard-deleted.
     */
    public function scoreSnapshots(): HasMany
    {
        return $this->hasMany(ArchiveScoreSnapshot::class, 'archive_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only rows that are still in the archived state (not yet restored or deleted).
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeRestored($query)
    {
        return $query->where('status', self::STATUS_RESTORED);
    }
}
