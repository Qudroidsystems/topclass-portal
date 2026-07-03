<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;
    protected $table = "subject";

    protected $fillable = [
        'subject',
        'subject_code',
        'remark',
    ];

    public function broadsheetRecords()
    {
        return $this->hasMany(BroadsheetRecord::class, 'subject_id', 'id');
    }
}
