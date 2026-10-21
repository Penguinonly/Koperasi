<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Http\Request;

class AdminLoanController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'collector_id' => ['required', 'exists:users,id'],
            'loan_amount' => ['required', 'numeric', 'min:1000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $loan = $customer->loans()->create($data + ['status' => 'PENDING_APPROVAL']);
        Approval::query()->create([
            'loan_id' => $loan->id,
            'requested_by' => $request->user()->id,
            'status' => 'PENDING',
        ]);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Pinjaman berhasil ditambahkan sebagai menunggu validasi.');
    }

    public function update(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'loan_amount' => ['required', 'numeric', 'min:1000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:PENDING_APPROVAL,APPROVED,REJECTED,ACTIVE,COMPLETED,DEFAULTED'],
        ]);
        $loan->update($data);

        return redirect()->route('admin.customers.show', $loan->customer_id)->with('success', 'Data pinjaman berhasil diperbarui.');
    }

    public function destroy(Loan $loan)
    {
        $customerId = $loan->customer_id;
        $loan->delete();

        return redirect()->route('admin.customers.show', $customerId)->with('success', 'Pinjaman berhasil dihapus.');
    }
}