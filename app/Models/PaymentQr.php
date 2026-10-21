<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentQr extends Model
{
    protected $fillable = [
        'loan_id',
        'collector_id',
        'type',
        'amount',
        'qr_signature',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function schedules()
    {
        return $this->belongsToMany(LoanSchedule::class, 'payment_qr_schedules');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'payment_qr_id');
    }
}
