<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['staffId','title', 'description', 'duration', 'start_time', 
    'end_time','termid','session','is_published','subject_id' ,'schoolclass_id'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
