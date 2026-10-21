<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\PaymentQr;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_dashboard_and_update_only_personal_details(): void
    {
        $this->seed(RoleSeeder::class);
        [$user, $customer] = $this->createMember('MEM-001');

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('Mulai scan')
            ->assertSee('Streak pembayaran');

        $this->actingAs($user)
            ->put(route('member.profile.update'), [
                'name' => 'Nama Baru',
                'phone' => '08123456789',
                'address' => 'Alamat Baru',
                'member_code' => 'MEM-OTHER',
                'status' => 'INACTIVE',
            ])
            ->assertRedirect(route('member.profile'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'member_code' => 'MEM-001',
            'name' => 'Nama Baru',
            'address' => 'Alamat Baru',
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru']);
    }

    public function test_customer_login_opens_member_dashboard_and_collector_is_denied(): void
    {
        $this->seed(RoleSeeder::class);
        [$user] = $this->createMember('MEM-005');

        $this->post(route('login.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('member.dashboard'));

        $this->get(route('member.dashboard'))->assertOk();

        $collector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
        ]);

        $this->actingAs($collector)
            ->get(route('member.dashboard'))
            ->assertForbidden();
    }

    public function test_customer_without_a_linked_member_profile_is_returned_to_login(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create([
            'role_id' => Role::query()->where('name', 'customer')->value('id'),
            'is_active' => true,
        ]);

        $this->from(route('login'))
            ->post(route('login.authenticate'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_member_streak_counts_consecutive_paid_schedules(): void
    {
        $this->seed(RoleSeeder::class);
        [$user, $customer] = $this->createMember('MEM-002');
        $collectorRoleId = Role::query()->where('name', 'collector')->value('id');
        $collector = User::factory()->create(['role_id' => $collectorRoleId]);
        $loan = Loan::query()->create([
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'loan_amount' => 8000000,
            'duration_days' => 60,
            'interest_rate' => 0,
            'status' => 'ACTIVE',
            'started_at' => now()->subDays(5),
        ]);

        foreach ([1 => 'PAID', 2 => 'PAID', 3 => 'UNPAID'] as $daysAgo => $status) {
            LoanSchedule::query()->create([
                'loan_id' => $loan->id,
                'day_number' => $daysAgo,
                'due_date' => today()->subDays($daysAgo),
                'amount_due' => 20000,
                'status' => $status,
            ]);
        }

        $this->actingAs($user)
            ->get(route('member.payments'))
            ->assertOk()
            ->assertSee('2 hari');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'streak_count' => 2]);
    }

    public function test_member_can_preview_and_pay_only_their_own_qr(): void
    {
        $this->seed(RoleSeeder::class);
        [$user, $customer] = $this->createMember('MEM-003');
        [$otherUser] = $this->createMember('MEM-004');
        $collector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
        ]);
        $loan = Loan::query()->create([
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'loan_amount' => 8000000,
            'duration_days' => 60,
            'interest_rate' => 0,
            'status' => 'ACTIVE',
            'started_at' => now()->subDay(),
        ]);
        $schedule = LoanSchedule::query()->create([
            'loan_id' => $loan->id,
            'day_number' => 1,
            'due_date' => today()->subDay(),
            'amount_due' => 20000,
            'status' => 'UNPAID',
        ]);
        $qr = PaymentQr::query()->create([
            'loan_id' => $loan->id,
            'collector_id' => $collector->id,
            'type' => 'ONE_DAY',
            'amount' => 20000,
            'status' => 'ACTIVE',
            'expires_at' => now()->addHour(),
        ]);
        $signature = Crypt::encryptString(json_encode([
            'payment_qr_id' => $qr->id,
            'loan_id' => $loan->id,
            'schedule_ids' => [$schedule->id],
            'expires_at' => now()->addHour()->toISOString(),
        ]));
        $qr->update(['qr_signature' => $signature]);
        $qr->schedules()->sync([$schedule->id]);

        $this->actingAs($otherUser)
            ->postJson(route('member.scan.preview'), ['qr_signature' => $signature])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('member.scan.preview'), ['qr_signature' => $signature])
            ->assertOk()
            ->assertJsonPath('customer_name', $customer->name)
            ->assertJsonPath('amount', 20000);

        $this->actingAs($otherUser)
            ->postJson(route('member.scan'), ['qr_signature' => $signature])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('member.scan'), ['qr_signature' => $signature])
            ->assertCreated()
            ->assertJsonPath('streak_updated', 1);

        $this->assertDatabaseHas('payments', ['payment_qr_id' => $qr->id, 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('loan_schedules', ['id' => $schedule->id, 'status' => 'PAID']);
    }

    private function createMember(string $memberCode): array
    {
        $user = User::factory()->create([
            'role_id' => Role::query()->where('name', 'customer')->value('id'),
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'user_id' => $user->id,
            'member_code' => $memberCode,
            'name' => $user->name,
            'status' => 'ACTIVE',
        ]);

        return [$user, $customer];
    }
}