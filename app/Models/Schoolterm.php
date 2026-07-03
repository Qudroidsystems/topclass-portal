<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schoolterm extends Model
{
    use HasFactory;

    protected $table = "schoolterm";

    protected $fillable = [
        'term',
        'status',
    ];

    // Cast the status field to boolean
    protected $casts = [
        'status' => 'boolean',
    ];

    // Scope for active terms only
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    // Scope for inactive terms
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }
}
