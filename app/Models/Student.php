<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['student_id', 'name', 'phone', 'chat_id', 'room_number', 'ic_number', 'password'];

    protected $hidden = ['password'];
}