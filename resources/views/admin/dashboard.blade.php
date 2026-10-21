@extends('layouts.admin')

@section('title', 'Dashboard Master Admin')

@section('content')
        <main class="mx-auto max-w-[1500px] px-5 py-7 lg:px-12">
            <section class="grid overflow-hidden bg-[#d8f0fb] lg:grid-cols-[1fr_1.4fr]">
                <div class="flex items-center justify-center p-5 lg:p-8">
                    <img src="{{ asset('images/koperasi-logo.png') }}" alt="Logo Koperasi Harmoni" class="h-48 w-48 object-contain lg:h-64 lg:w-64">
                </div>
                <div class="flex flex-col justify-center px-7 py-8">
                    <p class="text-sm italic">Welcome to</p>
                    <h1 class="text-4xl font-bold tracking-tight text-black lg:text-5xl">Koperasi Harmoni</h1>
                    <p class="mt-4 max-w-xl text-sm leading-6 text-[#25333b]">Tujuan koperasi adalah meningkatkan kesejahteraan anggota dan masyarakat secara umum. Kelola data karyawan, admin, dan nasabah dari satu ruang kerja.</p>
                    <a href="{{ route('admin.employees') }}" class="mt-5 w-fit rounded-full bg-[#063c55] px-6 py-2 text-sm font-semibold text-white hover:bg-[#07516f]">Cari Karyawan ⌕</a>
                </div>
            </section>

            <section class="grid grid-cols-3 divide-x border-b-2 border-[#a7cedd] bg-white py-6 text-center">
                <div><p class="text-xs font-semibold">Jumlah Karyawan</p><p class="mt-2 text-3xl font-bold">{{ \App\Models\User::whereNotNull('employee_id')->whereHas('role', fn ($query) => $query->where('name', 'collector'))->count() }}</p></div>
                <div><p class="text-xs font-semibold">Jumlah Admin</p><p class="mt-2 text-3xl font-bold">{{ \App\Models\User::whereHas('role', fn ($query) => $query->whereIn('name', ['admin', 'master_admin']))->count() }}</p></div>
                <div><p class="text-xs font-semibold">Jumlah Nasabah Aktif</p><p class="mt-2 text-3xl font-bold">{{ \App\Models\Customer::where('status', 'ACTIVE')->count() }}</p></div>
            </section>
        </main>
@endsection
