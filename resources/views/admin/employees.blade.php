@extends('layouts.admin')

@section('title', 'Data Karyawan Master Admin')

@section('content')
        <main class="mx-auto max-w-[1500px] px-5 py-5 lg:px-12">
            @if (session('success'))<div class="mb-4 border-l-4 border-[#063c55] bg-[#e2f4fa] px-4 py-3 text-sm">{{ session('success') }}</div>@endif
            <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <form method="GET" action="{{ route('admin.employees') }}" class="flex max-w-xl flex-1 gap-3">
                    <label class="flex h-10 flex-1 items-center rounded-full border border-[#c6d0d5] bg-white px-4"><span class="mr-2 text-[#9aa5aa]">⌕</span><input name="search" value="{{ request('search') }}" placeholder="search" class="w-full bg-transparent text-sm outline-none"></label>
                    <button class="rounded-full bg-[#063c55] px-7 text-sm font-semibold text-white" type="submit">Search</button>
                </form>
                <button type="button" onclick="document.getElementById('employee-form').classList.toggle('hidden')" class="rounded-full border border-[#b9c5ca] bg-white px-5 py-2 text-sm font-semibold text-[#063c55]">+ Tambah Karyawan</button>
            </div>

            <section id="employee-form" class="{{ $editingEmployee ? '' : 'hidden' }} mb-6 border border-[#d5dfe3] bg-white p-5 shadow-sm">
                <h1 class="mb-4 text-xl font-semibold">{{ $editingEmployee ? 'Edit Data Karyawan' : 'Tambah Data Karyawan' }}</h1>
                <form method="POST" action="{{ $editingEmployee ? route('admin.employees.update', $editingEmployee) : route('admin.employees.store') }}" class="grid gap-4 md:grid-cols-2">
                    @csrf
                    @if ($editingEmployee) @method('PUT') @endif
                    <label class="text-sm">Nama<input name="name" required value="{{ old('name', $editingEmployee?->name) }}" class="mt-1 block h-10 w-full border border-[#c6d0d5] px-3"></label>
                    <label class="text-sm">ID Karyawan<input name="employee_id" required value="{{ old('employee_id', $editingEmployee?->employee_id) }}" class="mt-1 block h-10 w-full border border-[#c6d0d5] px-3"></label>
                    <label class="text-sm">Email<input name="email" type="email" required value="{{ old('email', $editingEmployee?->email) }}" class="mt-1 block h-10 w-full border border-[#c6d0d5] px-3"></label>
                    <label class="text-sm">Password {{ $editingEmployee ? '(kosongkan jika tidak diubah)' : '' }}<input name="password" type="password" {{ $editingEmployee ? '' : 'required' }} class="mt-1 block h-10 w-full border border-[#c6d0d5] px-3"></label>
                    @if ($editingEmployee)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($editingEmployee->is_active)> Karyawan aktif</label>@endif
                    <div class="flex items-end gap-3"><button class="rounded bg-[#063c55] px-5 py-2 text-sm font-semibold text-white">Simpan</button><a href="{{ route('admin.employees') }}" class="rounded border px-5 py-2 text-sm">Batal</a></div>
                </form>
                @if ($errors->any())<div class="mt-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            </section>

            <div class="mb-3 flex items-center justify-between"><h2 class="text-xl font-semibold">Data Karyawan</h2><span class="text-xs text-[#66757d]">{{ $employees->total() }} data</span></div>
            <section class="overflow-x-auto border border-[#d5dfe3] bg-white shadow-sm">
                <table class="w-full min-w-[780px] border-collapse text-sm">
                    <thead class="bg-[#e8f5fa] text-left text-[#1c4658]"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">NIK / ID</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr class="border-t border-[#b7d7e2]"><td class="px-4 py-3 font-medium">{{ $employee->name }}</td><td class="px-4 py-3">{{ $employee->employee_id }}</td><td class="px-4 py-3">{{ $employee->email }}</td><td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs text-white {{ $employee->is_active ? 'bg-[#063c55]' : 'bg-[#d71920]' }}">{{ $employee->is_active ? 'Online' : 'Offline' }}</span></td><td class="px-4 py-3 text-right"><a href="{{ route('admin.employees', ['edit' => $employee->id]) }}" class="rounded bg-black px-4 py-1.5 text-xs text-white">Edit</a><form method="POST" action="{{ route('admin.employees.destroy', $employee) }}" class="ml-2 inline">@csrf @method('DELETE')<button onclick="return confirm('Nonaktifkan karyawan ini?')" class="rounded bg-black px-4 py-1.5 text-xs text-white">Delete</button></form></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-[#68777e]">Belum ada data karyawan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            <div class="mt-5">{{ $employees->links() }}</div>
        </main>
@endsection
