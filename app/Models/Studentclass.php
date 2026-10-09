<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Studentclass extends Model
{
    use HasFactory;
    protected $table = "studentclass";
    // The table's real primary key is `id`. It used to be declared as
    // "studentId", which made $row->update()/save() rewrite EVERY
    // studentclass row the student has (all sessions and terms), not
    // just the one row that was loaded.
    protected $primaryKey = "id";

    protected $fillable = [
        'studentId',
        'schoolclassid',
        'termid',
        'sessionid',

    ];


// In Studentclass model
public function schoolclass()
{
    return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
}

// In Schoolclass model
public function armRelation()
{
    return $this->belongsTo(Schoolarm::class, 'arm', 'id'); // Adjust based on your actual relationship
}



    /**
     * Subquery of one studentclass id per student enrolled in a class for a
     * session. A student has one row per term (promotion and term advance
     * write terms 1-3 separately), so a class+session listing that joins
     * studentclass without a term filter shows each student up to 3 times.
     * Use as ->whereIn('studentclass.id', Studentclass::oneRowPerStudent(...)).
     */
    public static function oneRowPerStudent($schoolclassId, $sessionId)
    {
        return DB::table('studentclass')
            ->selectRaw('MIN(id)')
            ->where('schoolclassid', $schoolclassId)
            ->where('sessionid', $sessionId)
            ->groupBy('studentId');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
}
}
