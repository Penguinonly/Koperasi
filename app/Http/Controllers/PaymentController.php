<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\PaymentQr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()->with(['customer', 'loan', 'qr']);

        if ($request->user()?->hasRole('customer')) {
            $query->whereHas('customer', fn ($customer) => $customer->where('user_id', $request->user()->id));
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 15)));
    }

    public function createQr(Request $request)
    {
        $data = $request->validate([
            'loan_id' => ['required', 'exists:loans,id'],
            'type' => ['required', 'in:ONE_DAY,TWO_DAYS,ARREARS_ACCUMULATED,CUSTOM'],
            'custom_amount' => ['nullable', 'numeric', 'min:1000'],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $loan = Loan::query()->with(['customer', 'schedules' => fn ($query) => $query->orderBy('day_number')])->findOrFail($data['loan_id']);

        if ($loan->status !== 'ACTIVE') {
            return response()->json(['message' => 'Loan is not active yet'], 422);
        }

        $schedules = $this->selectSchedules($loan, $data['type']);
        $amount = $data['type'] === 'CUSTOM'
            ? (float) ($data['custom_amount'] ?? 0)
            : (float) $schedules->sum('amount_due');

        if ($amount <= 0 || ($data['type'] !== 'CUSTOM' && $schedules->isEmpty())) {
            return response()->json(['message' => 'No payable schedules found'], 422);
        }

        $qr = PaymentQr::query()->create([
            'loan_id' => $loan->id,
            'collector_id' => $request->user()->id,
            'type' => $data['type'],
            'amount' => $amount,
            'status' => 'ACTIVE',
            'expires_at' => now()->addHours($data['expires_in_hours'] ?? 24),
        ]);

        $qr->schedules()->sync($schedules->pluck('id'));

        $signature = Crypt::encryptString(json_encode([
            'payment_qr_id' => $qr->id,
            'loan_id' => $loan->id,
            'schedule_ids' => $schedules->pluck('id')->values()->all(),
            'expires_at' => $qr->expires_at->toISOString(),
        ]));

        $qr->update(['qr_signature' => $signature]);

        return response()->json([
            'id' => $qr->id,
            'loan_id' => $qr->loan_id,
            'type' => $qr->type,
            'amount' => (float) $qr->amount,
            'qr_code' => base64_encode($signature),
            'qr_signature' => $signature,
            'status' => $qr->status,
            'expires_at' => $qr->expires_at,
            'created_at' => $qr->created_at,
        ], 201);
    }

    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_signature' => ['required_without:token', 'string'],
            'token' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'in:QR_CODE,TRANSFER,CASH'],
        ]);

        $signature = $data['qr_signature'] ?? $data['token'];

        try {
            $payload = json_decode(Crypt::decryptString($signature), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return response()->json(['message' => 'QR code expired or invalid'], 400);
        }

        $qr = PaymentQr::query()->with(['loan.schedules', 'loan.customer', 'schedules', 'payment'])->find($payload['payment_qr_id'] ?? null);

        if (!$qr || $qr->status !== 'ACTIVE' || $qr->expires_at->isPast()) {
            $qr?->update(['status' => 'EXPIRED']);

            return response()->json(['message' => 'QR code expired or invalid'], 400);
        }

        if ($qr->payment) {
            return response()->json(['message' => 'QR code already completed'], 409);
        }

        if ($request->user()?->hasRole('customer')) {
            abort_unless($request->user()->customer?->is($qr->loan->customer), 403, 'QR pembayaran ini bukan milik akun Anda.');
        }

        $payment = DB::transaction(function () use ($qr, $data) {
            $payment = Payment::query()->create([
                'loan_id' => $qr->loan_id,
                'customer_id' => $qr->loan->customer_id,
                'amount' => $qr->amount,
                'payment_method' => $data['payment_method'] ?? 'QR_CODE',
                'payment_qr_id' => $qr->id,
                'status' => 'COMPLETED',
                'reference_code' => 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
                'paid_at' => now(),
            ]);

            foreach ($qr->schedules as $schedule) {
                $schedule->update(['status' => 'PAID', 'paid_at' => now()]);
            }

            $qr->loan->customer->refreshPaymentStreak();
            $qr->update(['status' => 'COMPLETED']);

            if (!$qr->loan->schedules()->where('status', '!=', 'PAID')->exists()) {
                $qr->loan->update(['status' => 'COMPLETED', 'completed_at' => now()]);
            }

            return $payment;
        });

        return response()->json([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'amount' => (float) $payment->amount,
            'reference_code' => $payment->reference_code,
            'schedules_paid' => $qr->schedules->map(fn ($schedule) => [
                'day_number' => $schedule->day_number,
                'due_date' => $schedule->due_date->toDateString(),
            ])->values(),
            'message' => 'Payment successful. Thank you!',
            'loan_status' => $qr->loan->fresh()->status,
            'streak_updated' => $qr->loan->customer->fresh()->streak_count,
        ], 201);
    }

    public function previewScan(Request $request)
    {
        $data = $request->validate([
            'qr_signature' => ['required', 'string'],
        ]);

        try {
            $payload = json_decode(Crypt::decryptString($data['qr_signature']), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return response()->json(['message' => 'QR code tidak valid atau sudah kedaluwarsa.'], 400);
        }

        $qr = PaymentQr::query()->with(['loan.customer', 'payment'])->find($payload['payment_qr_id'] ?? null);

        if (!$qr || $qr->status !== 'ACTIVE' || $qr->expires_at->isPast() || $qr->payment) {
            return response()->json(['message' => 'QR code tidak aktif atau sudah digunakan.'], 400);
        }

        if ($request->user()?->hasRole('customer')) {
            abort_unless($request->user()->customer?->is($qr->loan->customer), 403, 'QR pembayaran ini bukan milik akun Anda.');
        }

        return response()->json([
            'customer_name' => $qr->loan->customer->name,
            'member_code' => $qr->loan->customer->member_code,
            'amount' => (float) $qr->amount,
            'expires_at' => $qr->expires_at,
        ]);
    }

    private function selectSchedules(Loan $loan, string $type)
    {
        $unpaid = $loan->schedules->where('status', '!=', 'PAID')->sortBy('day_number')->values();

        return match ($type) {
            'ONE_DAY' => $unpaid->take(1),
            'TWO_DAYS' => $unpaid->take(2),
            'ARREARS_ACCUMULATED' => $unpaid->filter(fn ($schedule) => Carbon::parse($schedule->due_date)->isBefore(today()))->values()->whenEmpty(fn () => $unpaid->take(1)),
            default => collect(),
        };
    }
}
