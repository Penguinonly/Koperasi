<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Services\SavingsLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSavingsController extends Controller
{
    public function show(Request $request, Customer $customer, SavingsLedgerService $ledger): JsonResponse
    {
        $filter = $request->validate([
            'category' => ['nullable', 'in:HISTORY,POKOK,WAJIB,SUKARELA'],
        ])['category'] ?? 'HISTORY';

        $latestTransaction = $customer->latestSavingsTransaction;
        $transactionQuery = $customer->savingsTransactions()
            ->with('recordedBy:id,name')
            ->latest('id');

        if ($filter !== 'HISTORY') {
            $transactionQuery->where('category', $filter);
        }

        $transactions = $transactionQuery->paginate(20)->withQueryString();

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'member_code' => $customer->member_code,
                'name' => $customer->name,
                'status' => $customer->status,
            ],
            'balance' => $latestTransaction?->balance_after ?? '0.00',
            'category_filter' => $filter,
            'category_totals' => $ledger->categoryTotals($customer),
            'uncategorized_transactions' => $customer->savingsTransactions()->whereNull('category')->count(),
            'transactions' => $transactions,
        ]);
    }

    public function store(Request $request, Customer $customer, SavingsLedgerService $ledger): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:DEPOSIT,WITHDRAWAL'],
            'category' => ['required', 'in:POKOK,WAJIB,SUKARELA'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'idempotency_key' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = $ledger->record(
            $customer,
            $request->user(),
            $data['type'],
            $data['category'],
            (string) $data['amount'],
            $data['idempotency_key'],
            $data['note'] ?? null,
        );

        return response()->json([
            'transaction' => $transaction->load('recordedBy:id,name'),
            'balance' => $transaction->balance_after,
        ], $transaction->wasRecentlyCreated ? 201 : 200);
    }
}
