<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AttendanceRecord;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function approvals(Request $request)
    {
        $query = Approval::query()->with(['loan.customer', 'requester:id,name,employee_id', 'approver:id,name,employee_id']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->upper());
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function approve(Request $request, Approval $approval)
    {
        $data = $request->validate(['notes' => ['nullable', 'string']]);

        $approval = DB::transaction(function () use ($approval, $request, $data) {
            $approval = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            abort_unless($approval->status === 'PENDING', 409, 'Pengajuan ini sudah diputuskan.');

            $loan = $approval->loan()->lockForUpdate()->firstOrFail();
            abort_unless($loan->status === 'PENDING_APPROVAL', 409, 'Status pinjaman tidak lagi menunggu validasi.');

            $approval->update([
                'status' => 'APPROVED',
                'approved_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
                'approval_date' => now(),
            ]);

            $loan->update([
                'status' => 'ACTIVE',
                'approved_by' => $request->user()->id,
                'approval_date' => now(),
                'started_at' => now(),
            ]);

            if (!$loan->schedules()->exists()) {
                $this->generateSchedules($loan, now()->addDay());
            }

            return $approval->fresh();
        });

        if (!$request->expectsJson()) {
            return redirect()->route('admin.loan-validations.show', $approval)->with('success', 'Pinjaman berhasil disetujui dan jadwal pembayaran dibuat.');
        }

        return response()->json([
            'id' => $approval->id,
            'status' => 'APPROVED',
            'approval_date' => $approval->fresh()->approval_date,
            'message' => 'Loan approved successfully',
        ]);
    }

    public function reject(Request $request, Approval $approval)
    {
        $data = $request->validate(['rejected_reason' => ['required', 'string', 'min:5', 'max:2000']]);

        $approval = DB::transaction(function () use ($approval, $request, $data) {
            $approval = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            abort_unless($approval->status === 'PENDING', 409, 'Pengajuan ini sudah diputuskan.');

            $loan = $approval->loan()->lockForUpdate()->firstOrFail();
            abort_unless($loan->status === 'PENDING_APPROVAL', 409, 'Status pinjaman tidak lagi menunggu validasi.');

            $approval->update([
                'status' => 'REJECTED',
                'approved_by' => $request->user()->id,
                'rejected_reason' => $data['rejected_reason'],
                'approval_date' => now(),
            ]);

            $loan->update(['status' => 'REJECTED']);

            return $approval->fresh();
        });

        if (!$request->expectsJson()) {
            return redirect()->route('admin.loan-validations.show', $approval)->with('success', 'Pinjaman ditolak dan alasan tersimpan.');
        }

        return response()->json([
            'id' => $approval->id,
            'status' => 'REJECTED',
            'rejected_reason' => $data['rejected_reason'],
            'message' => 'Loan rejected',
        ]);
    }

    public function arrearsByCollector()
    {
        $rows = LoanSchedule::query()
            ->selectRaw('loans.collector_id, users.name as collector_name, count(distinct loans.customer_id) as total_customers, count(distinct loans.id) as active_loans, sum(loan_schedules.amount_due) as total_arrears, count(*) as arrears_count')
            ->join('loans', 'loans.id', '=', 'loan_schedules.loan_id')
            ->join('users', 'users.id', '=', 'loans.collector_id')
            ->where('loans.status', 'ACTIVE')
            ->where('loan_schedules.status', '!=', 'PAID')
            ->whereDate('loan_schedules.due_date', '<', today())
            ->groupBy('loans.collector_id', 'users.name')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function attendance(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $records = AttendanceRecord::query()
            ->with('user:id,name,employee_id')
            ->whereDate('attendance_date', $date)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->upper()))
            ->get();

        return response()->json([
            'date' => $date,
            'total_employees' => User::query()->whereNotNull('employee_id')->count(),
            'attendance_summary' => [
                'complete' => $records->where('status', 'COMPLETE')->count(),
                'partial' => $records->where('status', 'PARTIAL')->count(),
                'absent' => $records->where('status', 'ABSENT')->count(),
            ],
            'records' => $records,
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'employee_id' => ['required', 'string', 'max:80', 'unique:users,employee_id'],
            'role' => ['nullable', 'in:collector,admin'],
        ]);

        $role = Role::query()->where('name', $data['role'] ?? 'collector')->first();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? 'password',
            'employee_id' => $data['employee_id'],
            'role_id' => $role?->id,
            'platform' => 'DESKTOP',
            'is_active' => true,
        ]);

        return response()->json($user->load('role'), 201);
    }

    private function generateSchedules(Loan $loan, Carbon $startDate): void
    {
        $cursor = $startDate->copy();
        $day = 1;
        $total = (float) $loan->loan_amount * (1 + ((float) $loan->interest_rate / 100));
        $daily = round($total / max(1, $loan->duration_days), 2);

        while ($day <= $loan->duration_days) {
            if (!$cursor->isSunday()) {
                LoanSchedule::query()->create([
                    'loan_id' => $loan->id,
                    'day_number' => $day,
                    'due_date' => $cursor->toDateString(),
                    'amount_due' => $daily,
                    'status' => 'UNPAID',
                ]);

                $day++;
            }

            $cursor->addDay();
        }
    }
}
