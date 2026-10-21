<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->with(['collector:id,name,employee_id', 'loans']);

        if ($request->user()?->hasRole('collector')) {
            $query->where('collector_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->upper());
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request)
    {
        $data = $this->validatedCustomer($request);

        $customer = Customer::query()->create($data + [
            'collector_id' => $request->user()->id,
            'status' => 'ACTIVE',
        ]);

        return response()->json($customer->load('collector:id,name,employee_id'), 201);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'member_code' => ['nullable', 'string', 'max:20', 'unique:customers,member_code'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:L,P'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_location' => ['nullable', 'array'],
            'id_number' => ['nullable', 'string', 'max:80', 'unique:customers,id_number'],
            'id_type' => ['nullable', 'string', 'max:20'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
        ]);

        $customer = DB::transaction(function () use ($data) {
            $user = null;

            if (!empty($data['email'])) {
                $role = Role::query()->where('name', 'customer')->first();
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'] ?? 'password',
                    'role_id' => $role?->id,
                    'platform' => 'ANDROID',
                    'is_active' => true,
                ]);
            }

            return Customer::query()->create([
                'user_id' => $user?->id,
                'member_code' => $data['member_code'] ?? null,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'address' => $data['address'] ?? null,
                'business_name' => $data['business_name'] ?? null,
                'business_location' => $data['business_location'] ?? null,
                'id_number' => $data['id_number'] ?? null,
                'id_type' => $data['id_type'] ?? 'KTP',
                'monthly_income' => $data['monthly_income'] ?? null,
                'status' => 'ACTIVE',
            ]);
        });

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load(['collector:id,name,employee_id', 'loans.schedules']));
    }

    private function validatedCustomer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'member_code' => ['nullable', 'string', 'max:20', 'unique:customers,member_code'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:L,P'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_location' => ['nullable', 'array'],
            'id_number' => ['nullable', 'string', 'max:80', 'unique:customers,id_number'],
            'id_type' => ['nullable', 'string', 'max:20'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
