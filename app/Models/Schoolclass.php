<?php

namespace App\Models;

use App\Models\Schoolarm;
use App\Models\Classcategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schoolclass extends Model
{
    use HasFactory;
    protected $table = "schoolclass";

    protected $fillable = ['schoolclass','arm','classcategoryid','description'];



    public function armRelation()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    public function classcategory()
    {
        return $this->belongsTo(Classcategory::class, 'classcategoryid', 'id');
    }

    public function arm()
    {
        return $this->belongsTo(Schoolarm::class, 'arm');
    }
    

}
