<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLoanValidationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:PENDING,APPROVED,REJECTED'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Approval::query()
            ->with(['loan.customer', 'loan.collector:id,name,employee_id', 'requester:id,name,employee_id'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest();

        $summaryQuery = Approval::query()
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
        $summary = $summaryQuery->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.loan-validations', [
            'approvals' => $query->paginate(12)->withQueryString(),
            'summary' => $summary,
            'active' => 'loan-validations',
        ]);
    }

    public function show(Approval $approval)
    {
        return view('admin.loan-validation-detail', [
            'approval' => $approval->load([
                'loan.customer',
                'loan.collector:id,name,employee_id',
                'loan.schedules',
                'requester:id,name,employee_id',
                'approver:id,name,employee_id',
            ]),
            'active' => 'loan-validations',
        ]);
    }
}