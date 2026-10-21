<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanTokenCounter extends Model
{
    protected $fillable = [
        'loan_id',
        'last_issued_sequence',
        'last_confirmed_sequence',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
