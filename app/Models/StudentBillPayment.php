<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBillPayment extends Model
{
    use HasFactory;

    protected $table = 'student_bill_payment';

    protected $fillable = [
        'student_id',
        'school_bill_id',
        'class_id',
        'termid_id',
        'session_id',
        'payment_method',
        'status',
        'generated_by',
        'delete_status',
        'created_at',
        'updated_at',
    ];

    /**
     * Define the relationship with StudentBillPaymentRecord.
     */
    public function schoolBill()
    {
        return $this->belongsTo(SchoolBillModel::class, 'school_bill_id', 'id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by', 'id');
    }

    public function studentBillPaymentRecords()
    {
        return $this->hasMany(StudentBillPaymentRecord::class, 'student_bill_payment_id', 'id');
    }
}