<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_access_profile(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        User::factory()->create([
            'name' => 'Budi Collector',
            'email' => 'collector.test@koperasi.id',
            'password' => 'password',
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
            'employee_id' => 'EMP-TST',
            'platform' => 'DESKTOP',
            'is_active' => true,
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'collector.test@koperasi.id',
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonPath('user.role.name', 'collector')
            ->assertJsonStructure(['token']);

        $this->withHeader('Authorization', 'Bearer '.$login->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'collector.test@koperasi.id');
    }

    public function test_collector_cannot_access_admin_routes(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $user = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
            'is_active' => true,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/approvals')
            ->assertForbidden();
    }
}
