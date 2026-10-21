<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class MasterAdminController extends Controller
{
    public function storeAdmin(Request $request)
    {
        return $this->storeUser($request, 'admin');
    }

    public function storeEmployee(Request $request)
    {
        return $this->storeUser($request, $request->input('role', 'collector'));
    }

    private function storeUser(Request $request, string $roleName)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'employee_id' => ['required', 'string', 'max:80', 'unique:users,employee_id'],
            'role' => ['nullable', 'in:collector,admin,master_admin'],
        ]);

        $role = Role::query()->where('name', $roleName)->firstOrFail();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? 'password',
            'employee_id' => $data['employee_id'],
            'role_id' => $role->id,
            'platform' => 'DESKTOP',
            'is_active' => true,
        ]);

        return response()->json($user->load('role'), 201);
    }
}
