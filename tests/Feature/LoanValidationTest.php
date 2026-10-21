<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 10:00:00');
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create([
            'role_id' => Role::query()->where('name', 'admin')->value('id'),
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_filter_approval_list_by_status_and_date(): void
    {
        $this->createApproval('Pending Member', 'PENDING', '2026-10-10');
        $this->createApproval('Approved Member', 'APPROVED', '2026-10-12');
        $this->createApproval('Rejected Member', 'REJECTED', '2026-10-14');

        $this->actingAs($this->admin)
            ->get(route('admin.loan-validations', [
                'status' => 'APPROVED',
                'from' => '2026-10-11',
                'to' => '2026-10-13',
            ]))
            ->assertOk()
            ->assertSee('Approved Member')
            ->assertDontSee('Pending Member')
            ->assertDontSee('Rejected Member')
            ->assertSee('Validasi Pinjaman');
    }

    public function test_collector_cannot_access_admin_loan_validation_pages(): void
    {
        $collector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
            'is_active' => true,
        ]);

        $this->actingAs($collector)
            ->get(route('admin.loan-validations'))
            ->assertForbidden();
    }

    public function test_admin_can_open_detail_and_approve_pending_loan_once(): void
    {
        $approval = $this->createApproval('Approval Detail Member', 'PENDING', '2026-10-15', 2);

        $this->actingAs($this->admin)
            ->get(route('admin.loan-validations.show', $approval))
            ->assertOk()
            ->assertSee('Approval Detail Member')
            ->assertSee('Keputusan validasi')
            ->assertSee('Setujui pinjaman');

        $this->post(route('admin.loan-validations.approve', $approval), ['notes' => 'Dokumen sudah diverifikasi.'])
            ->assertRedirect(route('admin.loan-validations.show', $approval));

        $this->assertDatabaseHas('approvals', [
            'id' => $approval->id,
            'status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'notes' => 'Dokumen sudah diverifikasi.',
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $approval->loan_id,
            'status' => 'ACTIVE',
            'approved_by' => $this->admin->id,
        ]);
        $this->assertDatabaseCount('loan_schedules', 2);

        $this->post(route('admin.loan-validations.reject', $approval), ['rejected_reason' => 'Alasan berbeda'])
            ->assertStatus(409);
        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'APPROVED']);
    }

    public function test_admin_must_supply_a_reason_to_reject_and_cannot_decide_twice(): void
    {
        $approval = $this->createApproval('Reject Detail Member', 'PENDING', '2026-10-15');

        $this->actingAs($this->admin)
            ->from(route('admin.loan-validations.show', $approval))
            ->post(route('admin.loan-validations.reject', $approval), ['rejected_reason' => 'No'])
            ->assertRedirect(route('admin.loan-validations.show', $approval))
            ->assertSessionHasErrors('rejected_reason');

        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'PENDING']);

        $this->post(route('admin.loan-validations.reject', $approval), [
            'rejected_reason' => 'Dokumen pendapatan belum memenuhi persyaratan.',
        ])->assertRedirect(route('admin.loan-validations.show', $approval));

        $this->assertDatabaseHas('approvals', [
            'id' => $approval->id,
            'status' => 'REJECTED',
            'rejected_reason' => 'Dokumen pendapatan belum memenuhi persyaratan.',
        ]);
        $this->assertDatabaseHas('loans', ['id' => $approval->loan_id, 'status' => 'REJECTED']);

        $this->post(route('admin.loan-validations.approve', $approval), ['notes' => 'Approve setelah reject'])
            ->assertStatus(409);
        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'REJECTED']);
    }

    private function createApproval(string $name, string $status, string $createdAt, int $durationDays = 60): Approval
    {
        $collector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'member_code' => (string) (Customer::query()->count() + 1),
            'name' => $name,
            'status' => 'ACTIVE',
        ]);
        $loan = Loan::query()->create([
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'loan_amount' => 3000000,
            'duration_days' => $durationDays,
            'interest_rate' => 2,
            'status' => match ($status) {
                'APPROVED' => 'ACTIVE',
                'REJECTED' => 'REJECTED',
                default => 'PENDING_APPROVAL',
            },
        ]);
        $approval = Approval::query()->create([
            'loan_id' => $loan->id,
            'requested_by' => $this->admin->id,
            'status' => $status,
        ]);
        $approval->forceFill([
            'created_at' => Carbon::parse($createdAt)->startOfDay(),
            'updated_at' => Carbon::parse($createdAt)->startOfDay(),
        ])->save();

        return $approval;
    }
}
