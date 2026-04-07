<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    use HasFactory;

    // This allows us to mass-save these fields
    protected $fillable = [
        'tracking_number', 
        'unique_id',    // <--- Added this
        'student_phone', 
        'student_id', 
        'shelf_label',
        'is_paid',
        'is_collected'
    ];
}