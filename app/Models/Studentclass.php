<?php

namespace App\Models;

use App\Models\Schoolarm;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Studentclass extends Model
{
    use HasFactory;
    protected $table = "studentclass";
    protected $primaryKey= "studentId";

    protected $fillable = [
        'studentId',
        'schoolclassid',
        'termid',
        'sessionid',

    ];

    public function armRelation()
    {
        return $this->belongsTo(Schoolarm::class, 'arm');
    }
    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
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
