<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'collector_id',
        'member_code',
        'name',
        'phone',
        'gender',
        'occupation',
        'address',
        'business_name',
        'business_location',
        'id_number',
        'id_type',
        'monthly_income',
        'status',
        'streak_count',
        'resignation_requested_at',
        'resigned_at',
        'exit_reason',
    ];

    protected $casts = [
        'business_location' => 'array',
        'monthly_income' => 'decimal:2',
        'resignation_requested_at' => 'date',
        'resigned_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refreshPaymentStreak(): int
    {
        $loan = $this->loans()
            ->whereIn('status', ['ACTIVE', 'COMPLETED'])
            ->latest('started_at')
            ->latest('id')
            ->first();

        if (!$loan) {
            $this->forceFill(['streak_count' => 0])->save();

            return 0;
        }

        $streak = 0;
        $statuses = $loan->schedules()
            ->whereDate('due_date', '<=', today())
            ->orderByDesc('due_date')
            ->get(['status', 'due_date']);

        foreach ($statuses as $schedule) {
            if ($schedule->status === 'PAID') {
                $streak++;
                continue;
            }

            if (Carbon::parse($schedule->due_date)->isToday()) {
                continue;
            }

            if ($schedule->status !== 'PAID') {
                break;
            }
        }

        $this->forceFill(['streak_count' => $streak])->save();

        return $streak;
    }
}
