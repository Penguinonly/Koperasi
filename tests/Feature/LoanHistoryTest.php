<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $collector;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-21 10:00:00');
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create([
            'role_id' => Role::query()->where('name', 'admin')->value('id'),
            'is_active' => true,
        ]);
        $this->collector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_filter_loan_history_by_status_search_and_date(): void
    {
        $this->createLoan('Searchable Member', 'ACTIVE', '2026-10-10');
        $this->createLoan('Other Member', 'ACTIVE', '2026-10-12');
        $this->createLoan('Completed Member', 'COMPLETED', '2026-10-12');

        $this->actingAs($this->admin)
            ->get(route('admin.loan-history', [
                'search' => 'Searchable',
                'status' => 'ACTIVE',
                'from' => '2026-10-09',
                'to' => '2026-10-11',
            ]))
            ->assertOk()
            ->assertSee('Searchable Member')
            ->assertDontSee('Other Member')
            ->assertDontSee('Completed Member')
            ->assertSee('Riwayat Pinjaman');
    }

    public function test_history_calculates_progress_and_overdue_after_partial_and_legacy_payments(): void
    {
        [$loan, $customer] = $this->createLoan('Progress Member', 'ACTIVE', '2026-10-10');
        $partialSchedule = $this->createSchedule($loan, 1, '2026-10-18', 'UNPAID', 100);
        $legacyPaidSchedule = $this->createSchedule($loan, 2, '2026-10-19', 'PAID', 100);
        $this->createSchedule($loan, 3, '2026-10-22', 'UNPAID', 100);
        $payment = Payment::query()->create([
            'loan_id' => $loan->id,
            'customer_id' => $customer->id,
            'amount' => 40,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
            'reference_code' => 'PAY-HISTORY-001',
            'paid_at' => now(),
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $payment->id,
            'loan_schedule_id' => $partialSchedule->id,
            'amount' => 40,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.loan-history', ['search' => 'Progress Member']))
            ->assertOk()
            ->assertSee('47%')
            ->assertSee('Rp 60')
            ->assertSee('1 jadwal lewat jatuh tempo')
            ->assertSee('1 / 3 jadwal lunas');

        $this->assertDatabaseHas('loan_schedules', ['id' => $legacyPaidSchedule->id, 'status' => 'PAID']);
    }

    public function test_collector_cannot_open_admin_loan_history(): void
    {
        $this->actingAs($this->collector)
            ->get(route('admin.loan-history'))
            ->assertForbidden();
    }

    private function createLoan(string $memberName, string $status, string $createdAt): array
    {
        $customer = Customer::query()->create([
            'member_code' => (string) (Customer::query()->count() + 1),
            'name' => $memberName,
            'status' => 'ACTIVE',
        ]);
        $loan = Loan::query()->create([
            'customer_id' => $customer->id,
            'collector_id' => $this->collector->id,
            'loan_amount' => 3000,
            'duration_days' => 60,
            'interest_rate' => 0,
            'status' => $status,
            'started_at' => Carbon::parse($createdAt)->startOfDay(),
        ]);
        $loan->forceFill([
            'created_at' => Carbon::parse($createdAt)->startOfDay(),
            'updated_at' => Carbon::parse($createdAt)->startOfDay(),
        ])->save();

        return [$loan, $customer];
    }

    private function createSchedule(Loan $loan, int $dayNumber, string $dueDate, string $status, float $amount): LoanSchedule
    {
        return LoanSchedule::query()->create([
            'loan_id' => $loan->id,
            'day_number' => $dayNumber,
            'due_date' => $dueDate,
            'amount_due' => $amount,
            'status' => $status,
            'paid_at' => $status === 'PAID' ? now()->subDay() : null,
        ]);
    }
}
