<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_code',
        'fingerprint_template_id',
        'employee_id',
        'user_id',
        'occurred_at',
        'accepted',
        'created_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'accepted' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
