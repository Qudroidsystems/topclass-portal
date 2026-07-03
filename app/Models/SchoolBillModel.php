<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolBillModel extends Model
{
    use HasFactory;
    protected $table = "school_bill";

    protected $fillable = [
        'title',
        'bill_amount',
        'description',
        'statusId',
    ];
}
