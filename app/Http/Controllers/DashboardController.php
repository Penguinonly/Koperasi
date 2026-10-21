<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->with('latestSavingsTransaction');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($memberQuery) use ($search) {
                $memberQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $status = $request->string('status', 'ACTIVE')->upper()->toString();
        if (!in_array($status, ['ACTIVE', 'INACTIVE', 'SUSPENDED', ''], true)) {
            $status = '';
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $members = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('dashboard', [
            'members' => $members,
            'totalMembers' => Customer::count(),
            'activeMembers' => Customer::where('status', 'ACTIVE')->count(),
            'selectedStatus' => $status,
        ]);
    }

    public function snapshot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:36'],
            'ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);

        $members = Customer::query()
            ->whereIn('id', $data['ids'])
            ->with('latestSavingsTransaction:id,customer_id,type,amount,balance_after,created_at')
            ->get(['id', 'status']);

        return response()->json([
            'active_members' => Customer::where('status', 'ACTIVE')->count(),
            'members' => $members->map(fn (Customer $member) => [
                'id' => $member->id,
                'status' => $member->status,
                'balance' => $member->latestSavingsTransaction?->balance_after ?? '0.00',
                'latest_transaction' => $member->latestSavingsTransaction ? [
                    'type' => $member->latestSavingsTransaction->type,
                    'amount' => $member->latestSavingsTransaction->amount,
                    'created_at' => $member->latestSavingsTransaction->created_at?->toIso8601String(),
                ] : null,
            ])->values(),
        ]);
    }
}
