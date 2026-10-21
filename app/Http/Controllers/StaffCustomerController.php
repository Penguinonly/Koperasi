<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Http\Request;

class StaffCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->withCount('loans')->orderBy('member_code');

        if ($request->user()->hasRole('collector')) {
            $query->where('collector_id', $request->user()->id);
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search')->trim();
            $query->where(fn ($customer) => $customer
                ->where('name', 'like', "%{$search}%")
                ->orWhere('member_code', (string) ((int) $search)));
        }

        return view('staff.customers', [
            'customers' => $query->paginate(12)->withQueryString(),
            'active' => 'customers',
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($request, $customer);

        return view('staff.customer-detail', [
            'customer' => $customer->load(['loans.collector', 'loans.schedules', 'payments']),
            'active' => 'customers',
        ]);
    }

    public function loan(Request $request, Customer $customer, Loan $loan)
    {
        $this->authorizeCustomer($request, $customer);

        abort_unless($loan->customer_id === $customer->id, 404);

        return view('staff.loan-detail', [
            'customer' => $customer,
            'loan' => $loan->load(['schedules', 'collector']),
            'active' => 'customers',
        ]);
    }

    private function authorizeCustomer(Request $request, Customer $customer): void
    {
        if ($request->user()->hasRole('collector') && $customer->collector_id !== $request->user()->id) {
            abort(403, 'Nasabah ini tidak ditugaskan kepada Anda.');
        }
    }
}