<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'loan_id',
        'requested_by',
        'approved_by',
        'status',
        'notes',
        'rejected_reason',
        'approval_date',
    ];

    protected $casts = [
        'approval_date' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
