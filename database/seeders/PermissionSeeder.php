<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'create_customer' => 'Create customer profile',
            'view_customer' => 'View customer details',
            'create_loan' => 'Create loan application',
            'approve_loan' => 'Approve loan application',
            'reject_loan' => 'Reject loan application',
            'create_payment_qr' => 'Create payment QR',
            'process_payment' => 'Process payment',
            'view_attendance' => 'View attendance',
            'post_attendance_logs' => 'Post IoT attendance logs',
            'manage_employees' => 'Manage employees',
            'manage_admins' => 'Manage admins',
            'view_reports' => 'View reports',
        ];

        foreach ($permissions as $name => $description) {
            Permission::query()->updateOrCreate(['name' => $name], ['description' => $description]);
        }

        $all = Permission::query()->pluck('id', 'name');

        $sets = [
            'customer' => ['process_payment'],
            'collector' => ['create_customer', 'view_customer', 'create_loan', 'create_payment_qr', 'process_payment', 'view_attendance'],
            'admin' => ['view_customer', 'approve_loan', 'reject_loan', 'view_attendance', 'manage_employees', 'view_reports'],
            'master_admin' => array_keys($permissions),
            'iot_device' => ['post_attendance_logs'],
        ];

        foreach ($sets as $roleName => $permissionNames) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role) {
                $role->permissions()->sync(collect($permissionNames)->map(fn ($name) => $all[$name])->all());
            }
        }
    }
}
