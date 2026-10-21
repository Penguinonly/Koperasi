<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'customer_id',
        'collector_id',
        'loan_amount',
        'duration_days',
        'interest_rate',
        'status',
        'approved_by',
        'approval_date',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'approval_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentTokens()
    {
        return $this->hasMany(PaymentToken::class);
    }

    public function tokenCounter()
    {
        return $this->hasOne(LoanTokenCounter::class);
    }

    public function approval()
    {
        return $this->hasOne(Approval::class);
    }
}
