@extends('layouts.workspace')

@section('title', 'Nasabah')

@section('content')
    <main class="mx-auto max-w-[1500px] px-4 py-5 lg:px-10">
        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-[#66757d]">{{ auth()->user()->hasRole('collector') ? 'Nasabah Saya' : 'Seluruh Nasabah' }}</p><h1 class="mt-1 text-2xl font-semibold">Data Nasabah</h1></div>
            <form method="GET" action="{{ route('workspace.customers') }}" class="flex w-full max-w-xl gap-3"><label class="flex h-10 flex-1 items-center rounded-full border border-[#c6d0d5] bg-white px-4"><span class="mr-2 text-[#9aa5aa]">⌕</span><input name="search" value="{{ request('search') }}" placeholder="Cari ID atau nama" class="w-full bg-transparent text-sm outline-none"></label><button class="rounded-full bg-[#063c55] px-7 text-sm font-semibold text-white">Search</button></form>
        </div>
        <section class="overflow-x-auto border border-[#d5dfe3] bg-white shadow-sm">
            <table class="w-full min-w-[820px] border-collapse text-sm"><thead class="bg-[#e8f5fa] text-left text-[#1c4658]"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Pekerjaan</th><th class="px-4 py-3">Alamat</th><th class="px-4 py-3">Pinjaman</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead><tbody>
                @forelse ($customers as $customer)
                    <tr class="border-t border-[#b7d7e2]"><td class="px-4 py-3 font-semibold text-[#063c55]">{{ $customer->member_code }}</td><td class="px-4 py-3 font-medium">{{ $customer->name }}</td><td class="px-4 py-3">{{ $customer->occupation ?: '-' }}</td><td class="max-w-72 px-4 py-3">{{ $customer->address ?: '-' }}</td><td class="px-4 py-3">{{ $customer->loans_count }}</td><td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs text-white {{ $customer->status === 'ACTIVE' ? 'bg-[#0b6b4f]' : 'bg-[#d71920]' }}">{{ $customer->status }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('workspace.customers.show', $customer) }}" class="rounded bg-[#063c55] px-4 py-1.5 text-xs text-white">Lihat</a></td></tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-[#68777e]">Nasabah tidak ditemukan.</td></tr>
                @endforelse
            </tbody></table>
        </section>
        <div class="mt-5">{{ $customers->links() }}</div>
    </main>
@endsection
