@extends('layouts.member')

@section('title', 'Beranda Anggota')

@section('content')
    <div class="grid gap-5 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
        <div class="space-y-5">
            <section class="border-b border-[#a7cedd] pb-5">
                <p class="text-sm text-[#66757d]">Selamat datang</p>
                <h1 class="mt-1 text-2xl font-semibold text-[#063c55]">{{ $customer->name }}</h1>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-[#66757d]">ID {{ $customer->member_code }} · {{ $customer->address ?: 'Alamat belum dilengkapi' }}</p>
                @if ($activeLoan)
                    <p class="mt-4 text-sm text-[#66757d]">Pinjaman aktif</p>
                    <p class="text-xl font-semibold text-[#d71920]">Rp {{ number_format($activeLoan->loan_amount, 0, ',', '.') }}</p>
                    <a href="{{ route('member.payments') }}" class="mt-3 inline-flex min-h-11 items-center rounded border border-[#a7cedd] px-4 text-sm font-semibold text-[#063c55] hover:bg-[#e8f5fa]">Lihat pembayaran</a>
                @else
                    <p class="mt-4 text-sm text-[#66757d]">Belum ada pinjaman aktif.</p>
                @endif
            </section>

            <section class="grid grid-cols-[1fr_auto] items-center gap-4 border-b border-[#a7cedd] pb-5" aria-label="Streak pembayaran">
                <div><p class="text-sm font-medium text-[#66757d]">Streak pembayaran</p><p class="mt-1 text-3xl font-bold text-[#063c55]">{{ $customer->streak_count }} <span class="text-base font-medium">hari</span></p><p class="mt-1 text-xs text-[#66757d]">Jadwal lunas beruntun pada pinjaman terakhir</p></div>
                <span class="grid h-14 w-14 place-items-center rounded-full bg-[#fff1c7] text-2xl text-[#b7791f]" aria-hidden="true">✦</span>
            </section>
        </div>

        <section class="border border-[#d5dfe3] bg-white p-4 sm:p-5" aria-labelledby="scan-heading">
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-xs font-semibold uppercase text-[#66757d]">Pembayaran</p><h2 id="scan-heading" class="mt-1 text-lg font-semibold text-[#063c55]">Scan QR dari petugas</h2></div>
                <span class="rounded-full bg-[#e8f5fa] px-3 py-1 text-xs font-semibold text-[#063c55]">Aman</span>
            </div>
            <div id="member-qr-reader" class="mt-4 min-h-56 overflow-hidden border border-dashed border-[#a7cedd] bg-[#f5f7f8]"></div>
            <p id="member-scan-status" class="mt-3 min-h-5 text-sm text-[#66757d]" aria-live="polite">Arahkan kamera ke QR pembayaran dari petugas.</p>
            <button id="member-scan-start" type="button" data-preview-url="{{ route('member.scan.preview') }}" data-scan-url="{{ route('member.scan') }}" class="mt-3 min-h-11 w-full rounded bg-[#063c55] px-4 text-sm font-semibold text-white hover:bg-[#07516f]">Mulai scan</button>
            <button id="member-scan-stop" type="button" hidden class="mt-2 min-h-11 w-full rounded border border-[#c6d0d5] px-4 text-sm font-semibold text-[#063c55] hover:bg-[#f5f7f8]">Hentikan kamera</button>
            <button id="member-scan-confirm" type="button" hidden class="mt-2 min-h-11 w-full rounded bg-[#0b6b4f] px-4 text-sm font-semibold text-white hover:bg-[#095a43]">Konfirmasi pembayaran</button>
            <p class="mt-3 text-xs leading-5 text-[#66757d]">Pastikan nama dan nominal pada QR sesuai sebelum meminta petugas menyelesaikan pembayaran.</p>
        </section>
    </div>

    <section class="mt-8" aria-labelledby="recent-payments-heading">
        <div class="mb-3 flex items-center justify-between gap-4"><h2 id="recent-payments-heading" class="text-lg font-semibold text-[#063c55]">Pembayaran terakhir</h2><a href="{{ route('member.payments') }}" class="text-sm font-semibold text-[#07516f]">Semua riwayat</a></div>
        <div class="divide-y divide-[#a7cedd] border-y border-[#a7cedd] bg-white">
            @forelse ($customer->payments as $payment)
                <div class="flex items-center justify-between gap-4 px-4 py-3"><div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $payment->loan?->status === 'COMPLETED' ? 'Pelunasan pinjaman' : 'Pembayaran angsuran' }}</p><p class="mt-1 text-xs text-[#66757d]">{{ $payment->paid_at?->format('d/m/Y H:i') }} · {{ $payment->reference_code }}</p></div><p class="shrink-0 text-sm font-semibold text-[#063c55]">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p></div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-[#66757d]">Belum ada pembayaran tercatat.</p>
            @endforelse
        </div>
    </section>
@endsection