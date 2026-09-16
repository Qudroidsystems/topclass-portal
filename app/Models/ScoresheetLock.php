<?php
// app/Models/ScoresheetLock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoresheetLock extends Model
{
    protected $table = 'scoresheet_locks';

    protected $fillable = [
        'subjectclass_id',
        'term_id',
        'session_id',
        'locked_by',
        'locked_at',
        'is_active',
        'reason',
        'scheduled_unlock_at',
    ];

    protected $casts = [
        'locked_at'           => 'datetime',
        'scheduled_unlock_at' => 'datetime',
        'is_active'           => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function subjectclass()
    {
        return $this->belongsTo(Subjectclass::class);
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function hasScheduledUnlock(): bool
    {
        return !is_null($this->scheduled_unlock_at);
    }

    public function isScheduledUnlockExpired(): bool
    {
        return $this->scheduled_unlock_at && $this->scheduled_unlock_at->isPast();
    }

    /**
     * Auto-expire this lock if scheduled_unlock_at has passed.
     * Returns true if it was expired (i.e. now inactive).
     */
    public function autoExpireIfDue(): bool
    {
        if ($this->is_active && $this->isScheduledUnlockExpired()) {
            $this->update(['is_active' => false]);
            return true;
        }
        return false;
    }
}