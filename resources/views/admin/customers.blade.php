@extends('layouts.admin')

@section('title', 'Data Nasabah')

@section('content')
    <main class="mx-auto max-w-[1500px] px-5 py-5 lg:px-12">
        @if (session('success'))<div class="mb-4 border-l-4 border-[#063c55] bg-[#e2f4fa] px-4 py-3 text-sm">{{ session('success') }}</div>@endif
        <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <form method="GET" action="{{ route('admin.customers') }}" class="flex max-w-xl flex-1 gap-3">
                <label class="flex h-10 flex-1 items-center rounded-full border border-[#c6d0d5] bg-white px-4"><span class="mr-2 text-[#9aa5aa]">⌕</span><input name="search" value="{{ request('search') }}" placeholder="Cari nama, ID anggota, pekerjaan" class="w-full bg-transparent text-sm outline-none"></label>
                <button class="rounded-full bg-[#063c55] px-7 text-sm font-semibold text-white" type="submit">Search</button>
            </form>
            <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('admin.customers') }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <select name="status" onchange="this.form.submit()" class="h-10 rounded-full border border-[#c6d0d5] bg-white px-4 text-sm outline-none">
                    <option value="" @selected(!request('status'))>Semua status</option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Aktif</option>
                    <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Tidak aktif</option>
                    <option value="SUSPENDED" @selected(request('status') === 'SUSPENDED')>Ditangguhkan</option>
                </select>
            </form>
            <form method="POST" action="{{ route('admin.customers.provision-users') }}">@csrf<button class="rounded-full border border-[#063c55] bg-white px-5 py-2 text-sm font-semibold text-[#063c55]" type="submit">Buat Akun Anggota</button></form>
            </div>
        </div>

        <div class="mb-3 flex items-center justify-between"><h1 class="text-xl font-semibold">Data Nasabah</h1><span class="text-xs text-[#66757d]">{{ $customers->total() }} data</span></div>
        <section class="overflow-x-auto border border-[#d5dfe3] bg-white shadow-sm">
            <table class="w-full min-w-[1050px] border-collapse text-sm">
                <thead class="bg-[#e8f5fa] text-left text-[#1c4658]"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">ID Anggota</th><th class="px-4 py-3">Jenis Kelamin</th><th class="px-4 py-3">Pekerjaan</th><th class="px-4 py-3">Alamat</th><th class="px-4 py-3">Akun</th><th class="px-4 py-3">Pinjaman</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-t border-[#b7d7e2] align-top"><td class="px-4 py-3 font-medium">{{ $customer->name }}</td><td class="px-4 py-3">{{ $customer->member_code }}</td><td class="px-4 py-3">{{ $customer->gender ?: '-' }}</td><td class="max-w-40 px-4 py-3">{{ $customer->occupation ?: '-' }}</td><td class="max-w-64 px-4 py-3">{{ $customer->address ?: '-' }}</td><td class="px-4 py-3">{{ $customer->user?->email ?: 'Belum dibuat' }}</td><td class="px-4 py-3">{{ $customer->loans_count }}</td><td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs text-white {{ $customer->status === 'ACTIVE' ? 'bg-[#0b6b4f]' : 'bg-[#d71920]' }}">{{ $customer->status }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('admin.customers.show', $customer) }}" class="rounded bg-black px-4 py-1.5 text-xs text-white">View</a></td></tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-[#68777e]">Belum ada data nasabah.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <div class="mt-5">{{ $customers->links() }}</div>
    </main>
@endsection