<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use ParagonIE\Sodium\Compat;
use Tests\TestCase;

class PaymentTokenSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $keypair = Compat::crypto_sign_keypair();
        config()->set('payment_tokens.private_key', base64_encode(Compat::crypto_sign_secretkey($keypair)));
        config()->set('payment_tokens.public_key', base64_encode(Compat::crypto_sign_publickey($keypair)));
        config()->set('payment_tokens.key_id', 'test-key-1');
        config()->set('payment_tokens.public_keys', ['test-key-1' => base64_encode(Compat::crypto_sign_publickey($keypair))]);
        config()->set('payment_tokens.sync_grace_hours', 24);
        config()->set('payment_tokens.daily_token_count', 3);
    }

    public function test_collector_issues_a_signed_idempotent_daily_bundle(): void
    {
        [$collector, , , $loan] = $this->createScenario();
        $this->assertTrue($collector->fresh()->hasRole('collector'));
        $this->assertTrue($collector->fresh()->is_active);

        $first = $this->apiAs($collector)
            ->postJson('/api/collector/sync/tokens', ['device_id' => 'collector-phone-1'])
            ->assertOk()
            ->assertJsonPath('loans.0.loan_id', $loan->id)
            ->assertJsonCount(3, 'loans.0.tokens');

        $token = $first->json('loans.0.tokens.0');
        $binarySignature = $this->decodeBase64Url($token['signature']);

        $this->assertTrue(Compat::crypto_sign_verify_detached(
            $binarySignature,
            $this->canonicalPayload($token['payload']),
            base64_decode(config('payment_tokens.public_key'), true),
        ));

        $second = $this->apiAs($collector)->postJson('/api/collector/sync/tokens', ['device_id' => 'collector-phone-1'])
            ->assertOk();

        $this->assertSame(
            $first->json('loans.0.tokens.0.payload.token_uuid'),
            $second->json('loans.0.tokens.0.payload.token_uuid'),
        );
        $this->assertSame(['test-key-1'], array_keys($first->json('public_keys')));
        $this->assertDatabaseHas('loan_token_counters', [
            'loan_id' => $loan->id,
            'last_issued_sequence' => 3,
        ]);
    }

    public function test_member_confirmation_is_partial_idempotent_and_audited(): void
    {
        [$collector, $member, $customer, $loan, $schedule] = $this->createScenario();
        $this->assertTrue($member->fresh()->hasRole('customer'));
        $this->assertTrue($member->fresh()->is_active);
        $this->apiAs($member)->getJson('/api/auth/me')->assertOk()->assertJsonPath('role.name', 'customer');
        $bundle = $this->apiAs($collector)
            ->postJson('/api/collector/sync/tokens', ['device_id' => 'collector-phone-1'])
            ->assertOk()
            ->json('loans.0.tokens.0');
        $claim = $this->claim($bundle, '40.00', 'member-phone-1');

        $accepted = $this->apiAs($member)
            ->postJson('/api/member/sync/confirm', ['claims' => [$claim]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'ACCEPTED');

        $paymentId = $accepted->json('results.0.payment_id');
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'payment_token_id' => $this->tokenId($bundle['payload']['token_uuid']),
            'customer_id' => $customer->id,
            'amount' => '40.00',
            'status' => 'COMPLETED',
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $paymentId,
            'loan_schedule_id' => $schedule->id,
            'amount' => '40.00',
        ]);
        $this->assertDatabaseHas('loan_schedules', ['id' => $schedule->id, 'status' => 'UNPAID']);

        $retry = $this->apiAs($collector)
            ->postJson('/api/collector/sync/confirm', ['claims' => [$this->claim($bundle, '40.00', 'collector-phone-1')]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'DUPLICATE')
            ->assertJsonPath('results.0.payment_id', $paymentId);

        $this->apiAs($collector)->postJson('/api/collector/sync/confirm', ['claims' => [$this->claim($bundle, '35.00', 'collector-phone-1')]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'SEQUENCE_CONFLICT');

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('token_sync_log', ['result' => 'ACCEPTED']);
        $this->assertDatabaseHas('token_sync_log', ['result' => 'DUPLICATE']);
        $this->assertDatabaseHas('token_sync_log', ['result' => 'SEQUENCE_CONFLICT']);
        $this->assertDatabaseHas('loan_token_counters', [
            'loan_id' => $loan->id,
            'last_confirmed_sequence' => 1,
        ]);
    }

    public function test_full_payment_marks_schedule_paid_and_streak_updates(): void
    {
        [$collector, $member, , , $schedule] = $this->createScenario();
        $bundle = $this->apiAs($collector)
            ->postJson('/api/collector/sync/tokens', ['device_id' => 'collector-phone-1'])
            ->assertOk()
            ->json('loans.0.tokens.0');

        $this->apiAs($member)
            ->postJson('/api/member/sync/confirm', ['claims' => [$this->claim($bundle, '100.00', 'member-phone-1')]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'ACCEPTED')
            ->assertJsonPath('results.0.streak_updated', 1);

        $this->assertDatabaseHas('loan_schedules', ['id' => $schedule->id, 'status' => 'PAID']);
    }

    public function test_invalid_signature_is_rejected_and_written_to_audit_log(): void
    {
        [$collector, $member] = $this->createScenario();
        $bundle = $this->apiAs($collector)
            ->postJson('/api/collector/sync/tokens', ['device_id' => 'collector-phone-1'])
            ->assertOk()
            ->json('loans.0.tokens.0');
        $bundle['signature'] = $this->encodeBase64Url(random_bytes(64));

        $this->apiAs($member)
            ->postJson('/api/member/sync/confirm', ['claims' => [$this->claim($bundle, '40.00', 'member-phone-1')]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'SIGNATURE_INVALID');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('token_sync_log', ['result' => 'SIGNATURE_INVALID']);
    }

    public function test_collector_cannot_issue_tokens_for_another_collectors_loan(): void
    {
        [$collector, , , $loan] = $this->createScenario();
        $otherCollector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
        ]);

        $response = $this->apiAs($otherCollector)
            ->postJson('/api/collector/sync/tokens', ['device_id' => 'other-phone'])
            ->assertOk();

        $this->assertSame([], $response->json('loans'));
        $this->assertDatabaseMissing('payment_tokens', ['loan_id' => $loan->id]);
        $this->assertNotNull($collector->id);
    }

    public function test_expiry_job_waits_until_the_sync_grace_window_has_elapsed(): void
    {
        [$collector] = $this->createScenario();
        $bundle = $this->apiAs($collector)
            ->postJson('/api/collector/sync/tokens', ['device_id' => 'collector-phone-1'])
            ->assertOk()
            ->json('loans.0.tokens.0');
        $token = \App\Models\PaymentToken::query()->where('token_uuid', $bundle['payload']['token_uuid'])->firstOrFail();
        $graceEnd = $token->expires_at->copy()->addHours(24);

        try {
            Carbon::setTestNow($graceEnd->copy()->subMinute());
            Artisan::call('tokens:expire');
            $this->assertDatabaseHas('payment_tokens', ['id' => $token->id, 'status' => 'ISSUED']);

            Carbon::setTestNow($graceEnd->copy()->addMinute());
            Artisan::call('tokens:expire');
            $this->assertDatabaseHas('payment_tokens', ['id' => $token->id, 'status' => 'EXPIRED']);
            $this->assertDatabaseHas('token_sync_log', ['token_uuid' => $token->token_uuid, 'result' => 'EXPIRED']);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createScenario(): array
    {
        $collector = User::factory()->create([
            'role_id' => Role::query()->where('name', 'collector')->value('id'),
        ]);
        $member = User::factory()->create([
            'role_id' => Role::query()->where('name', 'customer')->value('id'),
        ]);
        $customer = Customer::query()->create([
            'user_id' => $member->id,
            'collector_id' => $collector->id,
            'member_code' => 'TOK-001',
            'name' => $member->name,
            'status' => 'ACTIVE',
        ]);
        $loan = Loan::query()->create([
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'loan_amount' => 6000,
            'duration_days' => 60,
            'interest_rate' => 0,
            'status' => 'ACTIVE',
            'started_at' => now()->subDays(2),
        ]);
        $schedule = LoanSchedule::query()->create([
            'loan_id' => $loan->id,
            'day_number' => 1,
            'due_date' => today()->subDay(),
            'amount_due' => 100,
            'status' => 'UNPAID',
        ]);

        return [$collector, $member, $customer, $loan, $schedule];
    }

    private function apiAs(User $user)
    {
        Auth::forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('payment-token-test')->plainTextToken);
    }

    private function claim(array $token, string $amount, string $deviceId): array
    {
        return [
            'token' => [
                'payload' => $token['payload'],
                'signature' => $token['signature'],
            ],
            'confirmed_amount' => $amount,
            'payment_method' => 'CASH',
            'device_id' => $deviceId,
            'redeemed_offline_at' => now()->toIso8601String(),
        ];
    }

    private function tokenId(string $uuid): int
    {
        return \App\Models\PaymentToken::query()->where('token_uuid', $uuid)->value('id');
    }

    private function canonicalPayload(array $payload): string
    {
        $keys = ['version', 'key_id', 'token_uuid', 'loan_id', 'sequence', 'issued_by', 'max_amount_minor', 'issued_at', 'expires_at'];
        $ordered = [];
        foreach ($keys as $key) {
            $ordered[$key] = $payload[$key];
        }

        return json_encode($ordered, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function decodeBase64Url(string $encoded): string
    {
        $base64 = strtr($encoded, '-_', '+/');

        return base64_decode($base64.str_repeat('=', (4 - strlen($base64) % 4) % 4), true);
    }

    private function encodeBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}