<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolBillTermSession extends Model
{
    use HasFactory;

    protected $table = 'school_bill_class_term_session';

    protected $fillable = [
        'bill_id',
        'class_id',
        'termid_id',
        'session_id',
        'createdBy',
    ];

    /**
     * Define the relationship with SchoolBillModel.
     */
    public function bill()
    {
        return $this->belongsTo(SchoolBillModel::class, 'bill_id', 'id');
    }

    /**
     * Define the relationship with Schoolclass.
     */
    public function schoolClass()
    {
        return $this->belongsTo(Schoolclass::class, 'class_id', 'id');
    }

    /**
     * Define the relationship with Schoolterm.
     */
    public function term()
    {
        return $this->belongsTo(Schoolterm::class, 'termid_id', 'id');
    }

    /**
     * Define the relationship with Schoolsession.
     */
    public function session()
    {
        return $this->belongsTo(Schoolsession::class, 'session_id', 'id');
    }
}