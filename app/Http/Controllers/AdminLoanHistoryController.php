<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;

class AdminLoanHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:PENDING_APPROVAL,APPROVED,ACTIVE,COMPLETED,REJECTED,DEFAULTED'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $loans = Loan::query()
            ->with(['customer', 'collector:id,name,employee_id', 'schedules.allocations'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('customer', fn ($customer) => $customer
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $loans->getCollection()->each(function (Loan $loan) {
            $totalAmount = (float) $loan->schedules->sum('amount_due');
            $outstanding = 0.0;
            $overdue = 0.0;
            $overdueCount = 0;

            foreach ($loan->schedules as $schedule) {
                $allocated = (float) $schedule->allocations->sum('amount');
                $remaining = $schedule->status === 'PAID'
                    ? 0.0
                    : max(0, (float) $schedule->amount_due - $allocated);

                $outstanding += $remaining;
                if ($schedule->due_date->isBefore(today()) && $remaining > 0) {
                    $overdue += $remaining;
                    $overdueCount++;
                }
            }

            $loan->setAttribute('history_progress', $totalAmount > 0
                ? (int) round((($totalAmount - $outstanding) / $totalAmount) * 100)
                : 0);
            $loan->setAttribute('history_outstanding', round($outstanding, 2));
            $loan->setAttribute('history_overdue', round($overdue, 2));
            $loan->setAttribute('history_overdue_count', $overdueCount);
        });

        return view('admin.loan-history', [
            'loans' => $loans,
            'active' => 'history',
        ]);
    }
}