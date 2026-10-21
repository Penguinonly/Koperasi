@extends('layouts.admin')

@section('title', 'Detail Validasi Pinjaman')

@section('content')
    @php($loan = $approval->loan)
    @php($customer = $loan->customer)
    @php($statusClass = match ($approval->status) { 'APPROVED' => 'bg-[#e8f5ef] text-[#0b6b4f]', 'REJECTED' => 'bg-[#fff1f1] text-[#b91c1c]', default => 'bg-[#fff1c7] text-[#8a5a00]' })
    <main class="mx-auto max-w-[1200px] px-5 py-5 lg:px-12">
        @if (session('success'))<div class="mb-4 border-l-4 border-[#0b6b4f] bg-[#e8f5ef] px-4 py-3 text-sm text-[#0b6b4f]" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mb-4 border-l-4 border-[#d71920] bg-[#fff1f1] px-4 py-3 text-sm text-[#9f1239]" role="alert">{{ $errors->first() }}</div>@endif
        <div class="mb-5"><a href="{{ route('admin.loan-validations', request()->only(['status', 'from', 'to'])) }}" class="text-sm font-medium text-[#063c55]">&larr; Kembali ke validasi pinjaman</a></div>

        <div class="grid gap-5 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4"><div><p class="text-sm text-[#66757d]">{{ $customer->name }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-[#66757d]">ID Anggota {{ $customer->member_code }}</p><h1 class="mt-4 text-3xl font-bold text-[#063c55]">Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}</h1></div><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $approval->status === 'PENDING' ? 'Menunggu' : ($approval->status === 'APPROVED' ? 'Disetujui' : 'Ditolak') }}</span></div>
                <dl class="mt-6 grid grid-cols-2 gap-x-5 gap-y-4 border-t border-[#d5dfe3] pt-5 text-sm"><div><dt class="text-[#66757d]">Durasi</dt><dd class="mt-1 font-semibold">{{ $loan->duration_days }} hari</dd></div><div><dt class="text-[#66757d]">Bunga</dt><dd class="mt-1 font-semibold">{{ $loan->interest_rate }}%</dd></div><div><dt class="text-[#66757d]">Collector</dt><dd class="mt-1 font-semibold">{{ $loan->collector?->name ?: '-' }}</dd></div><div><dt class="text-[#66757d]">Diajukan oleh</dt><dd class="mt-1 font-semibold">{{ $approval->requester?->name ?: '-' }}</dd></div><div><dt class="text-[#66757d]">Tanggal pengajuan</dt><dd class="mt-1 font-semibold">{{ $approval->created_at?->format('d/m/Y H:i') }}</dd></div><div><dt class="text-[#66757d]">Tanggal keputusan</dt><dd class="mt-1 font-semibold">{{ $approval->approval_date?->format('d/m/Y H:i') ?: '-' }}</dd></div><div class="col-span-2"><dt class="text-[#66757d]">Alamat anggota</dt><dd class="mt-1 font-semibold">{{ $customer->address ?: 'Belum diisi' }}</dd></div></dl>
            </section>

            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold text-[#063c55]">Keputusan validasi</h2>
                @if ($approval->status === 'PENDING')
                    <p class="mt-1 text-sm text-[#66757d]">Setujui untuk mengaktifkan pinjaman dan membuat jadwal pembayaran.</p>
                    <form method="POST" action="{{ route('admin.loan-validations.approve', $approval) }}" class="mt-5 border-b border-[#d5dfe3] pb-5">@csrf<label class="text-sm font-medium">Catatan persetujuan<textarea name="notes" rows="3" maxlength="2000" class="mt-1 w-full border border-[#c6d0d5] px-3 py-2 font-normal" placeholder="Opsional"></textarea></label><button class="mt-3 min-h-10 rounded bg-[#0b6b4f] px-4 text-sm font-semibold text-white hover:bg-[#095a43]">Setujui pinjaman</button></form>
                    <form method="POST" action="{{ route('admin.loan-validations.reject', $approval) }}" class="mt-5">@csrf<label class="text-sm font-medium">Alasan penolakan<span class="ml-1 text-[#d71920]">*</span><textarea name="rejected_reason" rows="3" minlength="5" maxlength="2000" required class="mt-1 w-full border border-[#c6d0d5] px-3 py-2 font-normal" placeholder="Tuliskan alasan yang dapat ditindaklanjuti"></textarea></label><button class="mt-3 min-h-10 rounded bg-[#d71920] px-4 text-sm font-semibold text-white hover:bg-[#b91c1c]">Tolak pinjaman</button></form>
                @elseif ($approval->status === 'APPROVED')
                    <p class="mt-3 text-sm text-[#0b6b4f]">Pinjaman telah disetujui oleh {{ $approval->approver?->name ?: 'admin' }}.</p><p class="mt-4 text-sm text-[#66757d]">Catatan</p><p class="mt-1 text-sm">{{ $approval->notes ?: 'Tidak ada catatan.' }}</p>
                @else
                    <p class="mt-3 text-sm text-[#b91c1c]">Pinjaman ditolak oleh {{ $approval->approver?->name ?: 'admin' }}.</p><p class="mt-4 text-sm text-[#66757d]">Alasan penolakan</p><p class="mt-1 whitespace-pre-line text-sm">{{ $approval->rejected_reason ?: '-' }}</p>
                @endif
            </section>
        </div>

        <section class="mt-5 border border-[#d5dfe3] bg-white p-5 shadow-sm sm:p-6"><div class="flex items-center justify-between gap-4"><h2 class="text-lg font-semibold text-[#063c55]">Jadwal pembayaran</h2><span class="text-xs text-[#66757d]">{{ $loan->schedules->count() }} jadwal</span></div><div class="mt-4 grid gap-3 sm:grid-cols-3"><div class="border border-[#d5dfe3] p-4"><p class="text-xs text-[#66757d]">Status pinjaman</p><p class="mt-1 font-semibold">{{ $loan->status }}</p></div><div class="border border-[#d5dfe3] p-4"><p class="text-xs text-[#66757d]">Jadwal lunas</p><p class="mt-1 font-semibold">{{ $loan->schedules->where('status', 'PAID')->count() }}</p></div><div class="border border-[#d5dfe3] p-4"><p class="text-xs text-[#66757d]">Jadwal belum lunas</p><p class="mt-1 font-semibold">{{ $loan->schedules->where('status', '!=', 'PAID')->count() }}</p></div></div>
            @if ($loan->schedules->isNotEmpty())<div class="mt-4 divide-y divide-[#d5dfe3] border-y border-[#d5dfe3]">@foreach ($loan->schedules->sortBy('day_number')->take(8) as $schedule)<div class="grid grid-cols-[1fr_auto_auto] gap-4 py-3 text-sm"><span>Hari ke-{{ $schedule->day_number }} · {{ $schedule->due_date->format('d/m/Y') }}</span><span>Rp {{ number_format($schedule->amount_due, 0, ',', '.') }}</span><span class="font-semibold {{ $schedule->status === 'PAID' ? 'text-[#0b6b4f]' : 'text-[#8a5a00]' }}">{{ $schedule->status === 'PAID' ? 'Lunas' : 'Belum lunas' }}</span></div>@endforeach</div>@endif
        </section>
    </main>
@endsection
