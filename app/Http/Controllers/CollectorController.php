<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Customer;
use App\Models\LoanSchedule;
use Illuminate\Http\Request;

class CollectorController extends Controller
{
    public function customers(Request $request)
    {
        $customers = Customer::query()
            ->withCount(['loans as active_loans' => fn ($query) => $query->where('status', 'ACTIVE')])
            ->withSum(['payments as total_collected' => fn ($query) => $query->where('status', 'COMPLETED')], 'amount')
            ->where('collector_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($customers);
    }

    public function dailyMonitoring(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $schedules = LoanSchedule::query()
            ->with(['loan.customer', 'loan.collector:id,name,employee_id'])
            ->whereDate('due_date', $date)
            ->whereHas('loan', fn ($query) => $query->where('collector_id', $request->user()->id))
            ->orderBy('status')
            ->get();

        return response()->json([
            'date' => $date,
            'total_due' => (float) $schedules->sum('amount_due'),
            'paid_count' => $schedules->where('status', 'PAID')->count(),
            'unpaid_count' => $schedules->where('status', '!=', 'PAID')->count(),
            'data' => $schedules,
        ]);
    }

    public function leaderboard(Request $request)
    {
        $customers = Customer::query()
            ->where('collector_id', $request->user()->id)
            ->orderByDesc('streak_count')
            ->limit(10)
            ->get(['id', 'name', 'business_name', 'streak_count', 'status']);

        return response()->json(['data' => $customers]);
    }

    public function attendanceMe(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        return response()->json([
            'period' => $month,
            'records' => AttendanceRecord::query()
                ->where('user_id', $request->user()->id)
                ->where('attendance_date', 'like', $month.'%')
                ->orderBy('attendance_date')
                ->get(),
        ]);
    }
}
