<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBillPaymentBook extends Model
{
    use HasFactory;

    protected $table = 'student_bill_payment_book';

    protected $fillable = [
        'student_id',
        'school_bill_id',
        'amount_paid',
        'amount_owed',
        'payment_status',
        'class_id',
        'term_id',
        'session_id',
        'generated_by',
    ];
}
