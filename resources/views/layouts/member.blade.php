<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Anggota') · Koperasi Harmoni</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/member-qr-scanner.js'])
</head>
<body class="min-h-screen bg-[#f5f7f8] text-[#111827] antialiased">
    <header class="border-b border-[#d5dfe3] bg-white">
        <div class="mx-auto flex min-h-16 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('member.dashboard') }}" class="flex min-w-0 items-center gap-3" aria-label="Beranda anggota">
                <img src="{{ asset('images/koperasi-logo.png') }}" alt="" class="h-10 w-10 object-contain">
                <span class="truncate text-sm font-semibold text-[#063c55] sm:text-base">Ruang Anggota</span>
            </a>
            <nav class="hidden items-center gap-2 sm:flex" aria-label="Navigasi anggota">
                <a href="{{ route('member.dashboard') }}" class="rounded px-3 py-2 text-sm font-medium {{ request()->routeIs('member.dashboard') ? 'bg-[#e8f5fa] text-[#063c55]' : 'text-[#66757d] hover:text-[#063c55]' }}">Beranda</a>
                <a href="{{ route('member.payments') }}" class="rounded px-3 py-2 text-sm font-medium {{ request()->routeIs('member.payments') ? 'bg-[#e8f5fa] text-[#063c55]' : 'text-[#66757d] hover:text-[#063c55]' }}">Pembayaran</a>
                <a href="{{ route('member.profile') }}" class="rounded px-3 py-2 text-sm font-medium {{ request()->routeIs('member.profile') ? 'bg-[#e8f5fa] text-[#063c55]' : 'text-[#66757d] hover:text-[#063c55]' }}">Profil</a>
                <form method="POST" action="{{ route('logout') }}" class="ml-2">@csrf<button class="rounded border border-[#d5dfe3] px-3 py-2 text-sm text-[#66757d] hover:border-[#063c55] hover:text-[#063c55]">Keluar</button></form>
            </nav>
            <span class="max-w-28 truncate text-sm font-medium text-[#66757d] sm:hidden">{{ auth()->user()->customer?->name ?: auth()->user()->name }}</span>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 pb-24 pt-5 sm:px-6 sm:pb-10 sm:pt-8">
        @yield('content')
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-20 grid grid-cols-3 border-t border-[#d5dfe3] bg-white px-2 pb-[env(safe-area-inset-bottom)] sm:hidden" aria-label="Navigasi bawah">
        <a href="{{ route('member.dashboard') }}" class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('member.dashboard') ? 'font-semibold text-[#063c55]' : 'text-[#66757d]' }}"><span aria-hidden="true" class="text-lg">⌂</span>Beranda</a>
        <a href="{{ route('member.payments') }}" class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('member.payments') ? 'font-semibold text-[#063c55]' : 'text-[#66757d]' }}"><span aria-hidden="true" class="text-lg">▤</span>Pembayaran</a>
        <a href="{{ route('member.profile') }}" class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs {{ request()->routeIs('member.profile') ? 'font-semibold text-[#063c55]' : 'text-[#66757d]' }}"><span aria-hidden="true" class="text-lg">◉</span>Profil</a>
    </nav>
</body>
</html>