<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - Login</title>

        @vite(['resources/css/login.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#e6f3ff]/75 text-[#003049] antialiased">
        @if (session('status'))
            <div class="absolute right-8 top-6 z-10 rounded-lg border border-[#d9a441] bg-[#fff8df] px-5 py-3 text-sm text-[#704f00] shadow-sm">{{ session('status') }}</div>
        @endif
        <main class="relative min-h-screen w-full overflow-hidden bg-[#e6f3ff]/75">
            <section class="relative h-screen w-full">
                <div class="absolute inset-y-0 right-0 w-[768px] bg-[#e6f3ff]/80" aria-hidden="true"></div>
                <div class="absolute inset-y-0 right-[736px] w-[785px] border border-black bg-[#6d9a79] opacity-100" aria-hidden="true"></div>

                <div
                    class="absolute left-[118px] top-0 h-[1024px] w-[704px] bg-cover bg-center"
                    style="background-image: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=80');"
                    aria-label="City background"
                ></div>

                <form method="POST" action="{{ route('login.authenticate') }}" class="absolute left-[902px] top-20 flex h-[878px] w-[606px] flex-col" novalidate>
                    @csrf

                    <h1 class="ml-[130px] h-[38.13px] w-[337px] whitespace-nowrap text-center text-[46px] font-medium leading-normal tracking-[0] text-[#1e1e1e]">
                        Login
                    </h1>

                    <div class="mt-[224.9px] flex h-[92.85px] w-[602px] flex-col gap-[0.3px]">
                        <label for="email" class="ml-0.5 h-[29px] w-[119px] text-xl leading-normal text-[#003049]">
                            Username
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="Enter your Username here"
                            autocomplete="username"
                            required
                            class="h-16 w-[600px] rounded-[20px] border border-transparent bg-[#ffffffa6] px-10 text-xl leading-normal text-black placeholder:text-[#00000080] focus:border-[#003049] focus:bg-white"
                        >
                        @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div class="relative mt-[39.1px] h-[92.89px] w-[602px]">
                        <label for="password" class="absolute left-0.5 top-0 w-[98px] text-xl leading-normal text-[#003049]">
                            Password
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Enter your Password here"
                            autocomplete="current-password"
                            required
                            class="absolute left-0 top-[29px] h-16 w-[600px] rounded-[20px] border border-transparent bg-[#ffffffa6] px-10 text-xl leading-normal text-black placeholder:text-[#00000080] focus:border-[#003049] focus:bg-white"
                        >
                    </div>

                    <button
                        type="submit"
                        class="ml-[121px] mt-[39.1px] h-[59px] w-[340px] rounded-[7px] bg-[#003049] text-center text-[26px] leading-normal text-white transition-opacity duration-200 hover:opacity-90 focus:outline-2 focus:outline-offset-2 focus:outline-[#003049]"
                    >
                        Enter
                    </button>

                    <p class="ml-[159px] mt-[22.4px] h-[26.4px] w-[282px] text-lg leading-normal text-transparent">
                        <span class="text-[#7c838a]">Any problem ?</span>
                        <span class="text-black">&nbsp;</span>
                        <a href="mailto:contact@koperasiharmoni.com" class="text-[#003049] underline-offset-2 hover:underline focus:outline-2 focus:outline-offset-2 focus:outline-[#003049]">
                            contact centre
                        </a>
                    </p>

                    <p class="ml-[152px] mt-[221.1px] h-[22.49px] w-[279px] whitespace-nowrap text-center text-[34px] font-medium leading-normal text-[#003049]">
                        Koperasi Harmoni
                    </p>
                </form>

                <div class="absolute left-[93px] top-[273px] h-[477px] w-[492px]" aria-hidden="true">
                    <img
                        src="{{ asset('images/koperasi-logo.png') }}"
                        alt="Koperasi Indonesia emblem"
                        class="h-full w-full object-contain drop-shadow-[0_10px_18px_rgba(0,0,0,0.18)]"
                    >
                </div>
            </section>
        </main>
    </body>
</html>
