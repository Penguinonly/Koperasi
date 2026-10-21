<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Workspace') - Koperasi Harmoni</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f5f7f8] text-[#111827] antialiased">
        <header class="border-b border-[#d9e1e5] bg-white">
            <div class="mx-auto flex h-[68px] max-w-[1500px] items-center gap-6 px-4 lg:px-8">
                <a href="{{ route('workspace.customers') }}" class="flex shrink-0 items-center gap-2 text-[#063c55]">
                    <img src="{{ asset('images/koperasi-logo.png') }}" alt="Logo koperasi" class="h-12 w-12 object-contain">
                    <span class="hidden text-sm font-semibold text-[#111] md:inline">Dashboard</span>
                </a>
                <nav class="flex flex-1 items-center justify-center gap-5 text-xs font-semibold text-[#0b3c54] lg:gap-12 lg:text-sm" aria-label="Navigasi workspace">
                    <a class="flex items-center gap-2 {{ ($active ?? '') === 'customers' ? 'font-bold' : '' }}" href="{{ route('workspace.customers') }}"><span class="text-xl">♧</span> Nasabah</a>
                    <a class="flex items-center gap-2" href="#"><span class="text-xl">▣</span> Peminjaman</a>
                    <a class="hidden items-center gap-2 md:flex" href="#"><span class="text-xl">◷</span> Riwayat</a>
                </nav>
                @if (auth()->user()->hasRole('admin', 'master_admin'))
                    <a href="{{ route('admin.dashboard') }}" class="hidden text-xs font-semibold text-[#063c55] lg:inline">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-xl text-[#0b3c54]" title="Keluar" aria-label="Keluar">↪</button>
                </form>
            </div>
        </header>
        @yield('content')
    </body>
</html>
