<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::query()->with(['customer', 'collector:id,name,employee_id', 'schedules']);
        $user = $request->user();

        if ($user?->hasRole('collector')) {
            $query->where('collector_id', $user->id);
        } elseif ($user?->hasRole('customer')) {
            $query->whereHas('customer', fn ($customer) => $customer->where('user_id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->upper());
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'loan_amount' => ['required', 'numeric', 'min:1000'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $activeLoanExists = Loan::query()
            ->where('customer_id', $data['customer_id'])
            ->whereIn('status', ['PENDING_APPROVAL', 'APPROVED', 'ACTIVE'])
            ->exists();

        if ($activeLoanExists) {
            return response()->json(['message' => 'Customer already has an active or pending loan'], 422);
        }

        $loan = DB::transaction(function () use ($data, $request) {
            $loan = Loan::query()->create([
                'customer_id' => $data['customer_id'],
                'collector_id' => $request->user()->id,
                'loan_amount' => $data['loan_amount'],
                'duration_days' => $data['duration_days'] ?? 60,
                'interest_rate' => $data['interest_rate'] ?? 0,
                'status' => 'PENDING_APPROVAL',
            ]);

            Approval::query()->create([
                'loan_id' => $loan->id,
                'requested_by' => $request->user()->id,
                'status' => 'PENDING',
            ]);

            return $loan;
        });

        return response()->json($this->loanPayload($loan->load('customer')), 201);
    }

    public function show(Loan $loan)
    {
        return response()->json($this->loanPayload($loan->load(['customer', 'collector:id,name,employee_id', 'schedules', 'payments'])));
    }

    private function loanPayload(Loan $loan): array
    {
        $total = (float) $loan->loan_amount * (1 + ((float) $loan->interest_rate / 100));
        $daily = $total / max(1, $loan->duration_days);

        return [
            'id' => $loan->id,
            'customer' => $loan->customer,
            'collector_id' => $loan->collector_id,
            'loan_amount' => (float) $loan->loan_amount,
            'total_with_interest' => round($total, 2),
            'daily_payment' => round($daily, 2),
            'duration_days' => $loan->duration_days,
            'interest_rate' => (float) $loan->interest_rate,
            'status' => $loan->status,
            'started_at' => $loan->started_at,
            'schedules' => $loan->relationLoaded('schedules') ? $loan->schedules : [],
            'total_paid' => $loan->relationLoaded('payments') ? (float) $loan->payments->where('status', 'COMPLETED')->sum('amount') : null,
            'created_at' => $loan->created_at,
        ];
    }
}
