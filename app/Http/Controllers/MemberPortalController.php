<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class MemberPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer = $this->customer($request);
        $customer->refreshPaymentStreak();

        return view('member.dashboard', [
            'customer' => $customer->load([
                'loans' => fn ($query) => $query->latest('started_at')->latest('id'),
                'payments' => fn ($query) => $query->where('status', 'COMPLETED')->latest('paid_at')->limit(3),
            ]),
            'activeLoan' => $customer->loans->firstWhere('status', 'ACTIVE'),
        ]);
    }

    public function profile(Request $request)
    {
        return view('member.profile', ['customer' => $this->customer($request)]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:L,P'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'business_name' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = $this->customer($request);
        $customer->update($data);
        $request->user()->update(['name' => $data['name']]);

        return redirect()->route('member.profile')->with('success', 'Detail informasi berhasil diperbarui.');
    }

    public function payments(Request $request)
    {
        $customer = $this->customer($request);
        $customer->refreshPaymentStreak();

        return view('member.payments', [
            'customer' => $customer,
            'payments' => $customer->payments()
                ->with('loan')
                ->where('status', 'COMPLETED')
                ->latest('paid_at')
                ->paginate(10),
        ]);
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->user()->customer;

        abort_unless($customer, 404, 'Profil anggota belum terhubung ke akun ini.');

        return $customer;
    }
}