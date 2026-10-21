<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Admin') - Koperasi Harmoni</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f5f7f8] text-[#111827] antialiased">
        <header class="border-b border-[#d9e1e5] bg-white">
            <div class="mx-auto flex h-[68px] max-w-[1500px] items-center gap-8 px-5 lg:px-10">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 text-[#063c55]">
                    <img src="{{ asset('images/koperasi-logo.png') }}" alt="Logo koperasi" class="h-12 w-12 object-contain">
                    <span class="hidden text-sm font-semibold text-[#111] md:inline">Dashboard</span>
                </a>
                <nav class="flex flex-1 items-center justify-center gap-7 text-xs font-semibold text-[#0b3c54] lg:gap-12 lg:text-sm" aria-label="Navigasi admin">
                    <a class="flex items-center gap-2 {{ ($active ?? '') === 'dashboard' ? 'font-bold' : '' }}" href="{{ route('admin.dashboard') }}"><span class="text-2xl">⌂</span> Dashboard</a>
                    <a class="flex items-center gap-2 {{ ($active ?? '') === 'employees' ? 'font-bold' : '' }}" href="{{ route('admin.employees') }}"><span class="text-xl">♙</span> Data Karyawan</a>
                    <a class="hidden items-center gap-2 md:flex {{ ($active ?? '') === 'customers' ? 'font-bold' : '' }}" href="{{ route('admin.customers') }}"><span class="text-xl">♧</span> Data Nasabah</a>
                    <a class="hidden items-center gap-2 lg:flex {{ ($active ?? '') === 'loan-validations' ? 'font-bold' : '' }}" href="{{ route('admin.loan-validations') }}"><span class="text-xl">✓</span> Validasi Pinjaman</a>
                    <a class="hidden items-center gap-2 lg:flex {{ ($active ?? '') === 'history' ? 'font-bold' : '' }}" href="{{ route('admin.loan-history') }}"><span class="text-xl">◷</span> Riwayat Pinjaman</a>
                </nav>
                <details class="relative lg:hidden">
                    <summary class="cursor-pointer list-none p-2 text-xl text-[#063c55]" title="Buka navigasi admin" aria-label="Buka navigasi admin"><span aria-hidden="true">☰</span></summary>
                    <nav class="absolute right-0 top-full z-30 mt-2 grid min-w-48 border border-[#d5dfe3] bg-white p-2 shadow-lg" aria-label="Menu admin">
                        <a class="px-3 py-2 text-sm hover:bg-[#e8f5fa]" href="{{ route('admin.dashboard') }}">Dashboard</a>
                        <a class="px-3 py-2 text-sm hover:bg-[#e8f5fa]" href="{{ route('admin.employees') }}">Data Karyawan</a>
                        <a class="px-3 py-2 text-sm hover:bg-[#e8f5fa]" href="{{ route('admin.customers') }}">Data Nasabah</a>
                        <a class="px-3 py-2 text-sm font-semibold text-[#063c55] hover:bg-[#e8f5fa]" href="{{ route('admin.loan-validations') }}">Validasi Pinjaman</a>
                        <a class="px-3 py-2 text-sm hover:bg-[#e8f5fa]" href="{{ route('admin.loan-history') }}">Riwayat Pinjaman</a>
                    </nav>
                </details>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-xl text-[#0b3c54]" title="Keluar" aria-label="Keluar">↪</button>
                </form>
            </div>
        </header>

        @yield('content')
    </body>
</html>
