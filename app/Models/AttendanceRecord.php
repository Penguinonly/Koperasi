<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_date',
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out',
        'status',
        'calendar_color',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'morning_in' => 'datetime',
        'morning_out' => 'datetime',
        'afternoon_in' => 'datetime',
        'afternoon_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
