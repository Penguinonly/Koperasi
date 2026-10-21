<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = User::query()
            ->with('role')
            ->whereNotNull('employee_id')
            ->whereHas('role', fn ($query) => $query->where('name', 'collector'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($employee) => $employee
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.employees', [
            'employees' => $employees,
            'active' => 'employees',
            'editingEmployee' => $request->filled('edit')
                ? User::query()->with('role')->findOrFail($request->integer('edit'))
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $role = Role::query()->where('name', 'collector')->firstOrFail();

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'employee_id' => $data['employee_id'],
            'role_id' => $role->id,
            'platform' => 'DESKTOP',
            'is_active' => true,
        ]);

        return redirect()->route('admin.employees')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, User $employee)
    {
        abort_unless($employee->hasRole('collector'), 404);
        $data = $this->validated($request, $employee);
        $employee->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'employee_id' => $data['employee_id'],
            'is_active' => $request->boolean('is_active'),
            ...(!empty($data['password']) ? ['password' => $data['password']] : []),
        ]);

        return redirect()->route('admin.employees')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(User $employee)
    {
        abort_unless($employee->hasRole('collector'), 404);
        $employee->update(['is_active' => false]);

        return redirect()->route('admin.employees')->with('success', 'Karyawan dinonaktifkan.');
    }

    private function validated(Request $request, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee?->id)],
            'employee_id' => ['required', 'string', 'max:80', Rule::unique('users', 'employee_id')->ignore($employee?->id)],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }
}