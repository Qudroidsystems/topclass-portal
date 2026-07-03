<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadsheets extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadsheet_record_id',
        'subjectclass_id',
        'term_id',
        'staff_id',
        'ca1',
        'ca2',
        'ca3',
        'exam',
        'total',
        'bf', // Added brought forward
        'cum', // Added cumulative score
        'grade',
        'allsubjectstotalscores',
        'subjectpositionclass',
        'cmin',
        'cmax',
        'avg',
        'remark',
        'submiitedby',
        'vettedby',
        'vettedstatus'
    ];

    public function broadsheetRecord()
    {
        return $this->belongsTo(BroadsheetRecord::class,'broadsheet_record_id', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'term_id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id');
    }

    public function subjectclass()
    {
        return $this->belongsTo(Subjectclass::class, 'subjectclass_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
