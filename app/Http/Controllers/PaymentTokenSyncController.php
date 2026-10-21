<?php

namespace App\Http\Controllers;

use App\Services\PaymentTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class PaymentTokenSyncController extends Controller
{
    public function issue(Request $request, PaymentTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
        ]);

        try {
            return response()->json($tokens->issueDailyTokens($request->user(), $data['device_id']));
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Payment token signing keys are not configured.'], 503);
        }
    }

    public function confirm(Request $request, PaymentTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'claims' => ['required', 'array', 'min:1', 'max:100'],
            'claims.*.token' => ['required', 'array'],
            'claims.*.token.payload' => ['required', 'array'],
            'claims.*.token.signature' => ['required', 'string'],
            'claims.*.confirmed_amount' => ['required'],
            'claims.*.payment_method' => ['required', 'in:CASH'],
            'claims.*.device_id' => ['required', 'string', 'max:100'],
            'claims.*.redeemed_offline_at' => ['nullable', 'date'],
        ]);

        try {
            $results = collect($data['claims'])
                ->map(fn (array $claim) => $tokens->confirmClaim($request->user(), $claim))
                ->values();
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json(['message' => 'Payment token signing keys are not configured.'], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Token synchronization could not be completed.'], 500);
        }

        return response()->json(['results' => $results]);
    }
}
