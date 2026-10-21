<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->with('role.permissions')->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials']]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'User is inactive'], 403);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api-token')->plainTextToken;

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration'),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $role = Role::query()->where('name', 'customer')->first();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $role?->id,
            'platform' => $data['platform'] ?? 'ANDROID',
            'is_active' => true,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $this->userPayload($user->load('role.permissions')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()->load('role.permissions')));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function refresh(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'token' => $request->user()->createToken('api-token')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'description' => $user->role->description,
            ] : null,
            'permissions' => $user->permissionNames()->values(),
            'employee_id' => $user->employee_id,
            'platform' => $user->platform,
            'is_active' => $user->is_active,
        ];
    }
}
