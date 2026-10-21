@extends('layouts.workspace')

@section('title', 'Detail Nasabah')

@section('content')
    <main class="mx-auto max-w-[1500px] px-4 py-5 lg:px-10">
        <div class="mb-5 flex items-start justify-between"><div><a href="{{ route('workspace.customers') }}" class="text-sm text-[#063c55]">&larr; Kembali</a><h1 class="mt-2 text-2xl font-semibold">{{ $customer->name }}</h1><p class="text-sm text-[#66757d]">ID Anggota {{ $customer->member_code }} · {{ $customer->occupation ?: 'Pekerjaan belum diisi' }}</p></div><span class="rounded-full px-4 py-2 text-xs font-semibold text-white {{ $customer->status === 'ACTIVE' ? 'bg-[#0b6b4f]' : 'bg-[#d71920]' }}">{{ $customer->status }}</span></div>

        <div class="grid gap-5 xl:grid-cols-[0.85fr_1.15fr]">
            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm"><h2 class="mb-4 text-lg font-semibold">Informasi Anggota</h2><dl class="space-y-3 text-sm"><div class="flex justify-between gap-4 border-b border-[#edf1f2] pb-2"><dt class="text-[#66757d]">Nama</dt><dd class="text-right font-medium">{{ $customer->name }}</dd></div><div class="flex justify-between gap-4 border-b border-[#edf1f2] pb-2"><dt class="text-[#66757d]">Jenis kelamin</dt><dd class="font-medium">{{ $customer->gender ?: '-' }}</dd></div><div class="flex justify-between gap-4 border-b border-[#edf1f2] pb-2"><dt class="text-[#66757d]">Pekerjaan</dt><dd class="text-right font-medium">{{ $customer->occupation ?: '-' }}</dd></div><div class="border-b border-[#edf1f2] pb-2"><dt class="text-[#66757d]">Alamat</dt><dd class="mt-1 font-medium">{{ $customer->address ?: '-' }}</dd></div></dl></section>

            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Daftar Pinjaman</h2><span class="text-xs text-[#66757d]">{{ $customer->loans->count() }} pinjaman</span></div><div class="mt-4 space-y-3">@forelse ($customer->loans as $loan)<a href="{{ route('workspace.loans.show', [$customer, $loan]) }}" class="block border-t border-[#a7cedd] pt-3 transition hover:bg-[#f4fbfd]"><div class="flex items-start justify-between gap-4"><div><p class="font-semibold text-[#063c55]">Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}</p><p class="mt-1 text-xs text-[#66757d]">{{ $loan->duration_days }} hari · bunga {{ $loan->interest_rate }}% · {{ $loan->collector?->name ?: 'Collector belum dipilih' }}</p></div><span class="rounded-full px-3 py-1 text-xs font-semibold {{ in_array($loan->status, ['ACTIVE', 'APPROVED']) ? 'bg-[#d9f3e8] text-[#0b6b4f]' : ($loan->status === 'REJECTED' ? 'bg-[#ffe1e1] text-[#b91c1c]' : 'bg-[#fff1c7] text-[#8a5a00]') }}">{{ $loan->status }}</span></div></a>@empty<p class="py-8 text-center text-sm text-[#66757d]">Belum ada pinjaman.</p>@endforelse</div></section>
        </div>
    </main>
@endsection
