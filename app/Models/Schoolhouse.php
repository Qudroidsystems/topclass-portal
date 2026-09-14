<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schoolhouse extends Model
{
    use HasFactory;

    protected $table = 'schoolhouses';

    protected $fillable = [
        'house',
        'housecolour',
        'housemasterid',
        'termid',
        'sessionid',
    ];

    protected $casts = [
        'housemasterid' => 'integer',
        'termid'        => 'integer',
        'sessionid'     => 'integer',
    ];

    public function housemaster()
    {
        return $this->belongsTo(User::class, 'housemasterid', 'id');
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
