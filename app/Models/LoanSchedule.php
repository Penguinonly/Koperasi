<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    protected $fillable = [
        'loan_id',
        'day_number',
        'due_date',
        'amount_due',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function paymentQrs()
    {
        return $this->belongsToMany(PaymentQr::class, 'payment_qr_schedules');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
