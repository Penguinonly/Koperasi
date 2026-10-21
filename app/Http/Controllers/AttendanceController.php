<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function iotLog(Request $request)
    {
        $expectedKey = env('IOT_API_KEY');

        if ($expectedKey && $request->header('X-IOT-API-KEY') !== $expectedKey) {
            return response()->json(['accepted' => false, 'message' => 'Invalid API key'], 401);
        }

        $data = $request->validate([
            'deviceCode' => ['required', 'string', 'max:80'],
            'fingerprintTemplateId' => ['required', 'string', 'max:80'],
            'employeeId' => ['nullable', 'string', 'max:80'],
            'occurredAt' => ['nullable', 'date'],
        ]);

        $occurredAt = Carbon::parse($data['occurredAt'] ?? now());
        $user = !empty($data['employeeId'])
            ? User::query()->where('employee_id', $data['employeeId'])->first()
            : null;

        $slot = $this->slotFor($occurredAt);

        AttendanceLog::query()->create([
            'device_code' => $data['deviceCode'],
            'fingerprint_template_id' => $data['fingerprintTemplateId'],
            'employee_id' => $data['employeeId'] ?? null,
            'user_id' => $user?->id,
            'occurred_at' => $occurredAt,
            'accepted' => (bool) $user,
            'created_at' => now(),
        ]);

        $record = null;

        if ($user && $slot !== 'UNKNOWN') {
            $column = match ($slot) {
                'MORNING_IN' => 'morning_in',
                'MORNING_OUT' => 'morning_out',
                'AFTERNOON_IN' => 'afternoon_in',
                'AFTERNOON_OUT' => 'afternoon_out',
            };

            $record = AttendanceRecord::query()->firstOrCreate([
                'user_id' => $user->id,
                'attendance_date' => $occurredAt->toDateString(),
            ]);

            if (!$record->{$column}) {
                $record->{$column} = $occurredAt;
            }

            $this->refreshRecordStatus($record);
            $record->save();
        }

        return response()->json([
            'accepted' => (bool) $user,
            'slot' => $slot,
            'calendarColor' => $record?->calendar_color ?? 'RED',
            'message' => $user ? 'Attendance recorded' : 'Employee not found',
        ], 202);
    }

    public function me(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $records = AttendanceRecord::query()
            ->where('user_id', $request->user()->id)
            ->where('attendance_date', 'like', $month.'%')
            ->orderBy('attendance_date')
            ->get();

        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'employee_id']),
            'period' => $month,
            'statistics' => [
                'complete_days' => $records->where('status', 'COMPLETE')->count(),
                'partial_days' => $records->where('status', 'PARTIAL')->count(),
                'absent_days' => $records->where('status', 'ABSENT')->count(),
                'total_working_days' => $records->count(),
            ],
            'records' => $records,
        ]);
    }

    private function slotFor(Carbon $time): string
    {
        $minute = ($time->hour * 60) + $time->minute;

        return match (true) {
            $minute >= 7 * 60 && $minute <= (10 * 60) + 59 => 'MORNING_IN',
            $minute >= 11 * 60 && $minute <= (15 * 60) + 59 => 'MORNING_OUT',
            $minute >= 16 * 60 && $minute <= (17 * 60) + 29 => 'AFTERNOON_IN',
            $minute >= (17 * 60) + 30 && $minute <= (23 * 60) + 59 => 'AFTERNOON_OUT',
            default => 'UNKNOWN',
        };
    }

    private function refreshRecordStatus(AttendanceRecord $record): void
    {
        $filled = collect([
            $record->morning_in,
            $record->morning_out,
            $record->afternoon_in,
            $record->afternoon_out,
        ])->filter()->count();

        $record->status = $filled === 4 ? 'COMPLETE' : ($filled > 0 ? 'PARTIAL' : 'ABSENT');
        $record->calendar_color = $filled === 4 ? 'BLUE' : ($filled > 0 ? 'YELLOW' : 'RED');
    }
}
