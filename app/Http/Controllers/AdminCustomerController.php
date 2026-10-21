<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Services\MemberAccountProvisioner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::query()
            ->with(['user:id,email,is_active'])
            ->withCount('loans')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search')->trim();
                $query->where(function ($customer) use ($search) {
                    $customer->where('name', 'like', "%{$search}%")
                        ->orWhere('occupation', 'like', "%{$search}%");

                    if (ctype_digit((string) $search)) {
                        $customer->orWhere('member_code', (string) ((int) $search));
                    } else {
                        $customer->orWhere('member_code', 'like', "%{$search}%");
                    }
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->upper()))
            ->orderByRaw('CAST(member_code AS INTEGER)')
            ->paginate(12)
            ->withQueryString();

        return view('admin.customers', [
            'customers' => $customers,
            'active' => 'customers',
        ]);
    }

    public function provisionUsers(MemberAccountProvisioner $provisioner)
    {
        $created = $provisioner->provision();

        return redirect()->route('admin.customers')->with('success', "{$created} akun anggota berhasil dibuat.");
    }

    public function show(Customer $customer)
    {
        return view('admin.customer-detail', [
            'customer' => $customer->load(['loans.collector', 'loans.schedules', 'payments']),
            'employees' => \App\Models\User::query()->whereHas('role', fn ($query) => $query->where('name', 'collector'))->where('is_active', true)->orderBy('name')->get(),
            'active' => 'customers',
        ]);
    }

    public function loan(Customer $customer, Loan $loan)
    {
        abort_unless($loan->customer_id === $customer->id, 404);

        return view('admin.loan-detail', [
            'customer' => $customer,
            'loan' => $loan->load(['schedules', 'collector']),
            'active' => 'customers',
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validated($request, $customer);
        $customer->update($data + ['status' => $request->string('status')->upper()]);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Data nasabah berhasil diperbarui.');
    }

    private function validated(Request $request, Customer $customer): array
    {
        return $request->validate([
            'member_code' => ['required', 'string', 'max:20', Rule::unique('customers', 'member_code')->ignore($customer->id)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:L,P'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,SUSPENDED'],
            'resignation_requested_at' => ['nullable', 'date'],
            'resigned_at' => ['nullable', 'date'],
            'exit_reason' => ['nullable', 'string'],
        ]);
    }
}