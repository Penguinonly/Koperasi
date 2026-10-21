<?php

namespace App\Http\Controllers;

use App\Models\TokenSyncLog;
use Illuminate\Http\Request;

class AdminTokenReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'result' => ['nullable', 'in:ACCEPTED,DUPLICATE,SEQUENCE_CONFLICT,EXPIRED,SIGNATURE_INVALID,REJECTED'],
            'review' => ['nullable', 'in:UNREVIEWED,REVIEWED,FOLLOW_UP,NO_ACTION'],
            'search' => ['nullable', 'string', 'max:80'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = TokenSyncLog::query()
            ->with(['latestReview.reviewer:id,name'])
            ->when($filters['result'] ?? null, fn ($query, $result) => $query->where('result', $result))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '<=', $date))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('token_uuid', 'like', "%{$search}%"));

        if (($filters['review'] ?? null) === 'UNREVIEWED') {
            $query->whereDoesntHave('reviews');
        } elseif (in_array($filters['review'] ?? null, ['REVIEWED', 'FOLLOW_UP', 'NO_ACTION'], true)) {
            $resolution = $filters['review'];
            $query->whereHas('latestReview', fn ($review) => $review->where('resolution', $resolution));
        }

        $summary = TokenSyncLog::query()
            ->selectRaw('result, count(*) as total')
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '<=', $date))
            ->groupBy('result')
            ->pluck('total', 'result');

        return view('admin.token-reconciliation', [
            'logs' => $query->latest('submitted_at')->paginate(15)->withQueryString(),
            'summary' => $summary,
            'active' => 'token-reconciliation',
        ]);
    }

    public function review(Request $request, TokenSyncLog $syncLog)
    {
        $data = $request->validate([
            'resolution' => ['required', 'in:REVIEWED,FOLLOW_UP,NO_ACTION'],
            'notes' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $syncLog->reviews()->create([
            'token_uuid' => $syncLog->token_uuid,
            'reviewed_by' => $request->user()->id,
            'resolution' => $data['resolution'],
            'notes' => $data['notes'],
            'created_at' => now(),
        ]);

        return redirect()->route('admin.token-reconciliation', $request->only(['result', 'review', 'search', 'from', 'to']))
            ->with('success', 'Catatan rekonsiliasi berhasil ditambahkan. Catatan tidak mengubah status pembayaran.');
    }
}
