<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'recorded_by',
        'type',
        'amount',
        'balance_after',
        'idempotency_key',
        'reference_code',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}