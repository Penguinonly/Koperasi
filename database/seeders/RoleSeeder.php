<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'customer' => 'Customer/Nasabah',
            'collector' => 'Karyawan/Penagih',
            'admin' => 'Administrator',
            'master_admin' => 'Master Administrator',
            'iot_device' => 'ESP32 attendance device',
        ];

        foreach ($roles as $name => $description) {
            Role::query()->updateOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
