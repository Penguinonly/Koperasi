@extends('layouts.admin')

@section('title', 'Riwayat Pinjaman')

@section('content')
    <main class="mx-auto max-w-[1500px] px-5 py-5 lg:px-12">
        <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between"><div><p class="text-xs font-semibold uppercase tracking-wide text-[#66757d]">Portofolio pinjaman</p><h1 class="mt-1 text-2xl font-semibold text-[#063c55]">Riwayat Pinjaman</h1><p class="mt-1 text-sm text-[#66757d]">Pantau progres pembayaran dan tunggakan per pinjaman.</p></div><span class="text-sm text-[#66757d]">{{ $loans->total() }} pinjaman</span></div>

        <form method="GET" action="{{ route('admin.loan-history') }}" class="mb-5 grid gap-3 border border-[#d5dfe3] bg-white p-4 md:grid-cols-[1.2fr_1fr_1fr_1fr_auto] md:items-end">
            <label class="text-sm font-medium">Anggota<input name="search" value="{{ request('search') }}" placeholder="Cari nama atau ID anggota" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3 font-normal"></label>
            <label class="text-sm font-medium">Status<select name="status" class="mt-1 h-10 w-full border border-[#c6d0d5] bg-white px-3 font-normal"><option value="">Semua status</option>@foreach (['PENDING_APPROVAL' => 'Menunggu validasi', 'APPROVED' => 'Disetujui', 'ACTIVE' => 'Aktif', 'COMPLETED' => 'Lunas', 'REJECTED' => 'Ditolak', 'DEFAULTED' => 'Macet'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm font-medium">Dari tanggal<input type="date" name="from" value="{{ request('from') }}" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3 font-normal"></label>
            <label class="text-sm font-medium">Sampai tanggal<input type="date" name="to" value="{{ request('to') }}" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3 font-normal"></label>
            <div class="flex gap-2"><button class="h-10 rounded bg-[#063c55] px-5 text-sm font-semibold text-white hover:bg-[#07516f]">Terapkan</button><a href="{{ route('admin.loan-history') }}" class="flex h-10 items-center rounded border border-[#c6d0d5] px-4 text-sm text-[#66757d]">Reset</a></div>
        </form>

        <section class="overflow-x-auto border border-[#d5dfe3] bg-white shadow-sm">
            <table class="w-full min-w-[1080px] border-collapse text-sm">
                <thead class="bg-[#e8f5fa] text-left text-[#1c4658]"><tr><th class="px-4 py-3">Anggota / pinjaman</th><th class="px-4 py-3">Collector</th><th class="px-4 py-3">Nominal</th><th class="px-4 py-3">Progress</th><th class="px-4 py-3">Tunggakan</th><th class="px-4 py-3">Tanggal mulai</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($loans as $loan)
                        @php($statusClass = match ($loan->status) { 'ACTIVE' => 'bg-[#e8f5ef] text-[#0b6b4f]', 'COMPLETED' => 'bg-[#d8f0fb] text-[#063c55]', 'REJECTED', 'DEFAULTED' => 'bg-[#fff1f1] text-[#b91c1c]', default => 'bg-[#fff1c7] text-[#8a5a00]' })
                        <tr class="border-t border-[#b7d7e2] align-top"><td class="px-4 py-3"><p class="font-semibold text-[#063c55]">{{ $loan->customer?->name ?: '-' }}</p><p class="mt-1 text-xs text-[#66757d]">ID {{ $loan->customer?->member_code ?: '-' }} · Pinjaman #{{ $loan->id }}</p></td><td class="px-4 py-3">{{ $loan->collector?->name ?: '-' }}</td><td class="px-4 py-3 font-semibold">Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}</td><td class="px-4 py-3"><div class="flex items-center gap-2"><div class="h-2 w-28 bg-[#e8eef0]"><div class="h-2 bg-[#0b6b4f]" style="width: {{ $loan->history_progress }}%"></div></div><span class="text-xs font-semibold">{{ $loan->history_progress }}%</span></div><p class="mt-1 text-xs text-[#66757d]">{{ $loan->schedules->where('status', 'PAID')->count() }} / {{ $loan->schedules->count() }} jadwal lunas</p></td><td class="px-4 py-3"><p class="font-semibold {{ $loan->history_overdue > 0 ? 'text-[#b91c1c]' : 'text-[#0b6b4f]' }}">Rp {{ number_format($loan->history_overdue, 0, ',', '.') }}</p><p class="mt-1 text-xs text-[#66757d]">{{ $loan->history_overdue_count }} jadwal lewat jatuh tempo</p></td><td class="px-4 py-3 text-xs text-[#66757d]">{{ ($loan->started_at ?: $loan->created_at)?->format('d/m/Y') }}</td><td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $loan->status }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('admin.customers.loans.show', [$loan->customer, $loan]) }}" class="rounded bg-[#063c55] px-4 py-2 text-xs font-semibold text-white">Detail</a></td></tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-[#66757d]">Tidak ada pinjaman pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <div class="mt-5">{{ $loans->links() }}</div>
    </main>
@endsection
