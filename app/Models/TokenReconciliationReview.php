<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenReconciliationReview extends Model
{
    protected $fillable = [
        'token_sync_log_id',
        'token_uuid',
        'reviewed_by',
        'resolution',
        'notes',
    ];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
