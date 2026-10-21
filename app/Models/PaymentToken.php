<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentToken extends Model
{
    protected $fillable = [
        'token_uuid',
        'loan_id',
        'sequence',
        'issued_by',
        'max_amount',
        'issued_at',
        'expires_at',
        'signature',
        'key_id',
        'payload_version',
        'status',
        'redeemed_offline_at',
        'confirmed_at',
        'confirmed_amount',
        'device_id',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'confirmed_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'redeemed_offline_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
