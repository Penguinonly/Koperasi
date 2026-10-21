@extends('layouts.member')

@section('title', 'Riwayat Pembayaran')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-semibold uppercase text-[#66757d]">ID {{ $customer->member_code }}</p><h1 class="mt-1 text-2xl font-semibold text-[#063c55]">Riwayat pembayaran</h1></div>
        <div class="border-l-4 border-[#b7791f] bg-[#fff7df] px-4 py-2"><p class="text-xs font-medium text-[#8a5a00]">Streak saat ini</p><p class="text-xl font-bold text-[#063c55]">{{ $customer->streak_count }} hari</p></div>
    </div>

    <section class="overflow-hidden border border-[#d5dfe3] bg-white">
        <div class="hidden grid-cols-[1fr_1fr_1.2fr_1fr] gap-4 bg-[#e8f5fa] px-4 py-3 text-xs font-semibold uppercase text-[#1c4658] sm:grid"><span>Tanggal</span><span>Pinjaman</span><span>Referensi</span><span class="text-right">Nominal</span></div>
        <div class="divide-y divide-[#a7cedd]">
            @forelse ($payments as $payment)
                <article class="grid gap-2 px-4 py-4 sm:grid-cols-[1fr_1fr_1.2fr_1fr] sm:items-center sm:gap-4">
                    <div><p class="text-xs text-[#66757d] sm:hidden">Tanggal</p><p class="text-sm font-medium">{{ $payment->paid_at?->format('d/m/Y H:i') ?: '-' }}</p></div>
                    <div><p class="text-xs text-[#66757d] sm:hidden">Nomor pinjaman</p><p class="text-sm">#{{ $payment->loan_id }}</p></div>
                    <div><p class="text-xs text-[#66757d] sm:hidden">Referensi</p><p class="break-all text-xs text-[#66757d]">{{ $payment->reference_code }}</p><span class="mt-1 inline-block rounded bg-[#e8f5ef] px-2 py-0.5 text-xs font-semibold text-[#0b6b4f]">Berhasil</span></div>
                    <p class="text-sm font-semibold text-[#063c55] sm:text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                </article>
            @empty
                <p class="px-4 py-12 text-center text-sm text-[#66757d]">Belum ada riwayat pembayaran.</p>
            @endforelse
        </div>
    </section>
    <div class="mt-5">{{ $payments->links() }}</div>
@endsection