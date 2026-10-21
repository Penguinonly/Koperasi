<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            MemberSeeder::class,
        ]);

        $roles = \App\Models\Role::query()->pluck('id', 'name');

        $users = [
            ['name' => 'Admin Koperasi', 'email' => 'admin@koperasi.id', 'role' => 'admin', 'employee_id' => 'ADM001', 'platform' => 'DESKTOP'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                    'role_id' => $roles[$user['role']] ?? null,
                    'employee_id' => $user['employee_id'],
                    'platform' => $user['platform'],
                    'is_active' => true,
                ]
            );
        }
    }
}
