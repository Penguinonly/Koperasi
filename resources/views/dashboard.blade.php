<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Peminjaman Karyawan 1</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f7f7] text-[#171717] antialiased">
        <header class="border-b border-[#d8d8d8] bg-white">
            <div class="mx-auto flex h-20 max-w-[1440px] items-center gap-10 px-6 lg:px-10">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-4" aria-label="Beranda">
                    <img src="{{ asset('images/koperasi-logo.png') }}" alt="Logo koperasi" class="h-16 w-16 object-contain">
                    <span class="hidden text-xl font-semibold lg:inline">Dashboard</span>
                </a>
                <nav class="flex flex-1 items-center justify-center gap-6 text-lg font-semibold text-[#111] lg:gap-20" aria-label="Navigasi utama">
                    <a class="flex items-center gap-3 text-[#063c55]" href="{{ route('dashboard') }}"><span class="text-3xl">⌂</span> Peminjaman</a>
                    <a class="flex items-center gap-3 hover:text-[#063c55]" href="#"><span class="text-2xl">▣</span> Absensi</a>
                    <a class="flex items-center gap-3 hover:text-[#063c55]" href="#"><span class="text-2xl">◷</span> Riwayat</a>
                </nav>
                <span class="text-3xl text-[#063c55]" aria-hidden="true">⚙</span>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-6 py-5 lg:px-28">
            <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-center">
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-1 gap-3">
                    <label class="flex h-12 flex-1 items-center rounded-full border border-[#c8c8c8] bg-white px-5 shadow-sm">
                        <span class="mr-3 text-xl text-[#a5a5a5]">⌕</span>
                        <input name="search" value="{{ request('search') }}" placeholder="search" class="w-full border-0 bg-transparent text-lg outline-none placeholder:text-[#777]">
                    </label>
                    <button class="h-12 rounded-full bg-[#063c55] px-10 text-lg font-semibold text-white transition hover:bg-[#07516f]" type="submit">Search</button>
                </form>
                <form method="GET" action="{{ route('dashboard') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <select name="status" onchange="this.form.submit()" class="h-12 rounded-full border border-[#c8c8c8] bg-white px-6 text-lg font-semibold shadow-sm outline-none">
                        <option value="ACTIVE" @selected(request('status', 'ACTIVE') === 'ACTIVE')>Aktif</option>
                        <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Tidak aktif</option>
                        <option value="" @selected(request('status') === '')>Semua anggota</option>
                    </select>
                </form>
                <div class="flex h-12 items-center justify-center gap-3 rounded-full border border-[#c8c8c8] bg-white px-6 text-lg font-semibold shadow-sm">
                    <span>Jumlah Anggota: {{ $totalMembers }}</span><span class="text-2xl">♧</span>
                </div>
            </div>

            <div class="mb-4 flex items-center justify-between text-sm text-[#666]">
                <span>{{ $activeMembers }} anggota aktif</span>
                <span>{{ $members->total() }} hasil ditampilkan</span>
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Daftar anggota">
                @forelse ($members as $member)
                    @php($loan = $member->loans->sortByDesc('created_at')->first())
                    @php($isInactive = $member->status !== 'ACTIVE')
                    <article class="relative min-h-[190px] rounded-xl border border-[#e0e0e0] bg-white p-4 shadow-[0_2px_5px_rgba(0,0,0,0.12)]">
                        <span class="absolute right-0 top-0 rounded-bl-lg rounded-tr-xl px-3 py-1 text-sm font-semibold text-white {{ $isInactive ? 'bg-[#777]' : 'bg-[#063c55]' }}">
                            {{ $isInactive ? 'Tidak aktif' : 'Aktif' }}
                        </span>
                        <div class="pr-24">
                            <h2 class="truncate text-xl font-semibold">{{ $member->name }}</h2>
                            <p class="mt-2 text-xs font-medium uppercase tracking-wide text-[#777]">{{ $member->member_code }} · {{ $member->occupation ?: 'Pekerjaan belum diisi' }}</p>
                            <p class="mt-1 truncate text-sm text-[#555]">{{ $member->address ?: 'Alamat belum diisi' }}</p>
                        </div>
                        <p class="mt-4 text-sm font-medium text-[#333]">{{ $loan ? 'Rp. '.number_format($loan->loan_amount, 0, ',', '.') : 'Belum ada pinjaman' }}</p>
                        <div class="mt-3 flex gap-4">
                            <a href="#" class="flex-1 rounded-lg bg-[#063c55] py-2 text-center text-lg text-white transition hover:bg-[#07516f]">View</a>
                            <button type="button" class="flex-1 rounded-lg border border-[#063c55] py-2 text-lg text-[#063c55]">{{ $loan?->status === 'APPROVED' ? 'Verified' : 'Detail' }}</button>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl bg-white p-8 text-center text-[#666]">Tidak ada anggota yang cocok.</p>
                @endforelse
            </section>

            <div class="mt-6">{{ $members->links() }}</div>
        </main>
    </body>
</html>