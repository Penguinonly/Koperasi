<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'loan_id',
        'customer_id',
        'amount',
        'payment_method',
        'payment_qr_id',
        'payment_token_id',
        'status',
        'reference_code',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function qr()
    {
        return $this->belongsTo(PaymentQr::class, 'payment_qr_id');
    }

    public function token()
    {
        return $this->belongsTo(PaymentToken::class, 'payment_token_id');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
