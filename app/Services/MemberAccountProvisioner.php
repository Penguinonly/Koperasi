<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MemberAccountProvisioner
{
    public function provision(string $defaultPassword = 'password'): int
    {
        $role = Role::query()->where('name', 'customer')->firstOrFail();
        $created = 0;

        DB::transaction(function () use ($role, $defaultPassword, &$created) {
            Customer::query()->with('user')->whereNull('user_id')->orderBy('member_code')->each(function (Customer $customer) use ($role, $defaultPassword, &$created) {
                $email = "member{$customer->member_code}@koperasi.id";
                $user = User::query()->where('email', $email)->first();

                if (!$user) {
                    $user = User::query()->create([
                        'name' => $customer->name,
                        'email' => $email,
                        'password' => $defaultPassword,
                        'role_id' => $role->id,
                        'platform' => 'WEB',
                        'is_active' => $customer->status === 'ACTIVE',
                    ]);
                }

                $customer->update(['user_id' => $user->id]);
                $created++;
            });
        });

        return $created;
    }
}