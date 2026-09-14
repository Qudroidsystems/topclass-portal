<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Principalscomment extends Model
{
    use HasFactory;

    protected $table = 'principalscomments';

    protected $fillable = [
        'staffId',
        'schoolclassid',
        'sessionid',
        'termid',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staffId', 'id');
    }

    public function schoolclass()
    {
        return $this->belongsTo(Schoolclass::class, 'schoolclassid', 'id');
    }

    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'sessionid', 'id');
    }

    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid', 'id');
    }
}
