<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'loan_schedule_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'loan_schedule_id');
    }
}
