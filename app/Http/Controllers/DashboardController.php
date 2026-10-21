<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->with('loans');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($memberQuery) use ($search) {
                $memberQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->upper());
        }

        $members = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('dashboard', [
            'members' => $members,
            'totalMembers' => Customer::count(),
            'activeMembers' => Customer::where('status', 'ACTIVE')->count(),
        ]);
    }
}