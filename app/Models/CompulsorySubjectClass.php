<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompulsorySubjectClass extends Model
{
     use HasFactory;
    protected $table = "compulsory_subject_classes";

    protected $fillable = [
        'subjectId',
        'schoolclassid',
    ];
}
