<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TokenSyncLog extends Model
{
    protected $table = 'token_sync_log';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'meta' => 'array',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(TokenReconciliationReview::class, 'token_sync_log_id');
    }

    public function latestReview(): HasOne
    {
        return $this->hasOne(TokenReconciliationReview::class, 'token_sync_log_id')->latestOfMany('created_at');
    }
}
