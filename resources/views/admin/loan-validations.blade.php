@extends('layouts.admin')

@section('title', 'Validasi Pinjaman')

@section('content')
    <main class="mx-auto max-w-[1500px] px-5 py-5 lg:px-12">
        @if (session('success'))<div class="mb-4 border-l-4 border-[#0b6b4f] bg-[#e8f5ef] px-4 py-3 text-sm text-[#0b6b4f]" role="status">{{ session('success') }}</div>@endif
        <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-[#66757d]">Persetujuan pinjaman</p><h1 class="mt-1 text-2xl font-semibold text-[#063c55]">Validasi Pinjaman</h1><p class="mt-1 text-sm text-[#66757d]">Tinjau pengajuan sebelum pinjaman diaktifkan.</p></div>
            <span class="text-sm text-[#66757d]">{{ $approvals->total() }} pengajuan</span>
        </div>

        <section class="mb-5 grid gap-3 sm:grid-cols-3" aria-label="Ringkasan status">
            <div class="border border-[#d5dfe3] bg-white px-4 py-3"><p class="text-xs text-[#66757d]">Menunggu</p><p class="mt-1 text-xl font-semibold text-[#8a5a00]">{{ $summary['PENDING'] ?? 0 }}</p></div>
            <div class="border border-[#d5dfe3] bg-white px-4 py-3"><p class="text-xs text-[#66757d]">Disetujui</p><p class="mt-1 text-xl font-semibold text-[#0b6b4f]">{{ $summary['APPROVED'] ?? 0 }}</p></div>
            <div class="border border-[#d5dfe3] bg-white px-4 py-3"><p class="text-xs text-[#66757d]">Ditolak</p><p class="mt-1 text-xl font-semibold text-[#b91c1c]">{{ $summary['REJECTED'] ?? 0 }}</p></div>
        </section>

        <form method="GET" action="{{ route('admin.loan-validations') }}" class="mb-5 grid gap-3 border border-[#d5dfe3] bg-white p-4 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end">
            <label class="text-sm font-medium">Status<select name="status" class="mt-1 h-10 w-full border border-[#c6d0d5] bg-white px-3 font-normal"><option value="">Semua status</option><option value="PENDING" @selected(request('status') === 'PENDING')>Menunggu</option><option value="APPROVED" @selected(request('status') === 'APPROVED')>Disetujui</option><option value="REJECTED" @selected(request('status') === 'REJECTED')>Ditolak</option></select></label>
            <label class="text-sm font-medium">Dari tanggal<input type="date" name="from" value="{{ request('from') }}" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3 font-normal"></label>
            <label class="text-sm font-medium">Sampai tanggal<input type="date" name="to" value="{{ request('to') }}" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3 font-normal"></label>
            <div class="flex gap-2"><button class="h-10 rounded bg-[#063c55] px-5 text-sm font-semibold text-white hover:bg-[#07516f]">Terapkan</button><a href="{{ route('admin.loan-validations') }}" class="flex h-10 items-center rounded border border-[#c6d0d5] px-4 text-sm text-[#66757d]">Reset</a></div>
        </form>

        <section class="overflow-x-auto border border-[#d5dfe3] bg-white shadow-sm">
            <table class="w-full min-w-[900px] border-collapse text-sm">
                <thead class="bg-[#e8f5fa] text-left text-[#1c4658]"><tr><th class="px-4 py-3">Nasabah</th><th class="px-4 py-3">Nominal</th><th class="px-4 py-3">Durasi</th><th class="px-4 py-3">Collector</th><th class="px-4 py-3">Diajukan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($approvals as $approval)
                        @php($statusClass = match ($approval->status) { 'APPROVED' => 'bg-[#e8f5ef] text-[#0b6b4f]', 'REJECTED' => 'bg-[#fff1f1] text-[#b91c1c]', default => 'bg-[#fff1c7] text-[#8a5a00]' })
                        <tr class="border-t border-[#b7d7e2] align-top"><td class="px-4 py-3"><p class="font-semibold text-[#063c55]">{{ $approval->loan?->customer?->name ?: '-' }}</p><p class="mt-1 text-xs text-[#66757d]">ID {{ $approval->loan?->customer?->member_code ?: '-' }}</p></td><td class="px-4 py-3 font-semibold">Rp {{ number_format($approval->loan?->loan_amount ?: 0, 0, ',', '.') }}</td><td class="px-4 py-3">{{ $approval->loan?->duration_days ?: '-' }} hari</td><td class="px-4 py-3">{{ $approval->loan?->collector?->name ?: '-' }}</td><td class="px-4 py-3 text-xs text-[#66757d]">{{ $approval->created_at?->format('d/m/Y H:i') }}</td><td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $approval->status === 'PENDING' ? 'Menunggu' : ($approval->status === 'APPROVED' ? 'Disetujui' : 'Ditolak') }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('admin.loan-validations.show', $approval) }}" class="rounded bg-[#063c55] px-4 py-2 text-xs font-semibold text-white">Lihat detail</a></td></tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-[#66757d]">Tidak ada pengajuan pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <div class="mt-5">{{ $approvals->links() }}</div>
    </main>
@endsection
