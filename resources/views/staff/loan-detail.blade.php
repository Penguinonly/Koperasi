@extends('layouts.workspace')

@section('title', 'Detail Pinjaman')

@section('content')
    @php
        $monthDate = request('month') ? \Carbon\Carbon::createFromFormat('Y-m', request('month'))->startOfMonth() : now()->startOfMonth();
        $monthStart = $monthDate->copy()->startOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $calendarEnd = $monthDate->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $schedulesByDate = $loan->schedules->keyBy(fn ($schedule) => $schedule->due_date->toDateString());
        $calendarDays = [];
        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
            $calendarDays[] = $day->copy();
        }
    @endphp
    <main class="mx-auto max-w-[1500px] px-4 py-5 lg:px-10">
        <div class="mb-5"><a href="{{ route('workspace.customers.show', $customer) }}" class="text-sm text-[#063c55]">&larr; Kembali ke detail anggota</a></div>
        <div class="grid gap-5 xl:grid-cols-[0.8fr_1.2fr]">
            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-3"><div><p class="text-sm text-[#66757d]">{{ $customer->name }}</p><p class="mt-1 text-xs text-[#66757d]">{{ $customer->occupation ?: 'Pekerjaan belum diisi' }}</p><h1 class="mt-3 text-2xl font-bold text-[#d71920]">Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}</h1></div><span class="rounded-full bg-[#fff1c7] px-3 py-1 text-xs font-semibold text-[#8a5a00]">{{ $loan->status }}</span></div><div class="mt-5 grid grid-cols-2 gap-3 border-t pt-4 text-sm"><div><p class="text-[#66757d]">Durasi</p><p class="font-semibold">{{ $loan->duration_days }} hari</p></div><div><p class="text-[#66757d]">Bunga</p><p class="font-semibold">{{ $loan->interest_rate }}%</p></div><div><p class="text-[#66757d]">Collector</p><p class="font-semibold">{{ $loan->collector?->name ?: '-' }}</p></div><div><p class="text-[#66757d]">Total jadwal</p><p class="font-semibold">{{ $loan->schedules->count() }}</p></div></div><div class="mt-5 border-t pt-4"><p class="text-sm font-semibold">Detail anggota</p><p class="mt-2 text-sm text-[#66757d]">{{ $customer->address ?: 'Alamat belum diisi' }}</p></div></section>

            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm"><div class="mb-4 flex items-center justify-between"><a href="?month={{ $monthDate->copy()->subMonth()->format('Y-m') }}" class="text-xl text-[#063c55]">&lsaquo;</a><h2 class="font-semibold uppercase tracking-wide text-[#314d73]">{{ $monthDate->format('M Y') }}</h2><a href="?month={{ $monthDate->copy()->addMonth()->format('Y-m') }}" class="text-xl text-[#063c55]">&rsaquo;</a></div><div class="grid grid-cols-7 gap-2 text-center text-[10px] font-semibold text-[#66757d]">@foreach (['MON','TUE','WED','THU','FRI','SAT','SUN'] as $weekday)<span>{{ $weekday }}</span>@endforeach</div><div class="mt-3 grid grid-cols-7 gap-2">@foreach ($calendarDays as $calendarDay) @php($schedule = $schedulesByDate->get($calendarDay->toDateString()))<div class="flex aspect-square items-center justify-center rounded-full text-xs {{ $calendarDay->month !== $monthDate->month ? 'text-transparent' : ($schedule?->status === 'PAID' ? 'bg-[#d9f3e8] text-[#0b6b4f]' : ($schedule ? 'bg-[#fff1c7] text-[#8a5a00]' : 'bg-[#eef1f2] text-[#8a969b]')) }}">{{ $calendarDay->month === $monthDate->month ? ($schedule && $schedule->status !== 'PAID' ? '🔥' : $calendarDay->day) : '' }}</div>@endforeach</div><div class="mt-4 flex gap-4 border-t pt-3 text-[11px] text-[#66757d]"><span><i class="mr-1 inline-block h-2 w-2 rounded-full bg-[#fff1c7]"></i>Belum lunas</span><span><i class="mr-1 inline-block h-2 w-2 rounded-full bg-[#d9f3e8]"></i>Lunas</span></div></section>
        </div>

        <section class="mt-5 border border-[#d5dfe3] bg-white p-5 shadow-sm"><h2 class="mb-3 text-lg font-semibold">Jadwal Pembayaran</h2><div class="divide-y divide-[#a7cedd]">@forelse ($loan->schedules->sortByDesc('day_number')->take(12) as $schedule)<div class="grid items-center gap-3 py-3 md:grid-cols-[80px_1fr_140px_120px]"><p class="text-lg font-bold text-[#063c55]">Day-{{ $schedule->day_number }}</p><div><p class="text-xs text-[#66757d]">Nama: <span class="ml-2 text-[#111827]">{{ $customer->name }}</span></p><p class="text-xs text-[#66757d]">No. ID: <span class="ml-1 text-[#111827]">{{ $customer->member_code }}</span></p></div><p class="text-xs text-[#66757d]">{{ $schedule->due_date->format('d/m/Y') }}<br><span class="font-semibold text-[#111827]">Rp {{ number_format($schedule->amount_due, 0, ',', '.') }}</span></p><span class="text-xs font-semibold {{ $schedule->status === 'PAID' ? 'text-[#0b6b4f]' : 'text-[#d71920]' }}">{{ $schedule->status === 'PAID' ? 'Lunas' : 'Belum Lunas' }}</span></div>@empty<p class="py-8 text-center text-sm text-[#66757d]">Belum ada jadwal pembayaran untuk pinjaman ini.</p>@endforelse</div></section>
    </main>
@endsection
