<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasFactory;

    protected $table = "staffbioinfo";
    protected $primaryKey = "id";

    protected $fillable = [
        'userid',
        'title',
        'employmentid',
        'phonenumber',
        'email',
        'gender',
        'maritalstatus',
        'numberofchildren',
        'spousenumber',
        'address',
        'nationality',
        'state',
        'local',
        'religion',
        'dateofbirth',
    ];

    /**
     * Get the user associated with the Staff
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    /**
     * Get formatted date of birth
     */
    public function getFormattedDateOfBirthAttribute(): ?string
    {
        if ($this->dateofbirth) {
            try {
                return date('F j, Y', strtotime($this->dateofbirth));
            } catch (\Exception $e) {
                return $this->dateofbirth;
            }
        }

        return null;
    }
}
