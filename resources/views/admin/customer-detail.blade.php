@extends('layouts.admin')

@section('title', 'Detail Nasabah')

@section('content')
    <main class="mx-auto max-w-[1500px] px-5 py-5 lg:px-12">
        @if (session('success'))<div class="mb-4 border-l-4 border-[#063c55] bg-[#e2f4fa] px-4 py-3 text-sm">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mb-4 border-l-4 border-[#d71920] bg-[#fff1f1] px-4 py-3 text-sm text-[#9f1239]">{{ $errors->first() }}</div>@endif

        <div class="mb-5 flex items-start justify-between gap-4">
            <div><a href="{{ route('admin.customers') }}" class="text-sm text-[#063c55]">&larr; Kembali ke Data Nasabah</a><h1 class="mt-2 text-2xl font-semibold">{{ $customer->name }}</h1><p class="text-sm text-[#66757d]">ID Anggota {{ $customer->member_code }} · {{ $customer->occupation ?: 'Pekerjaan belum diisi' }}</p></div>
            <span class="rounded-full px-4 py-2 text-xs font-semibold text-white {{ $customer->status === 'ACTIVE' ? 'bg-[#0b6b4f]' : 'bg-[#d71920]' }}">{{ $customer->status }}</span>
        </div>

        <div class="grid gap-5 xl:grid-cols-[0.85fr_1.15fr]">
            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">Informasi Anggota</h2>
                <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="grid gap-4">
                    @csrf @method('PUT')
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm">ID Anggota<input name="member_code" value="{{ old('member_code', $customer->member_code) }}" required class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label><label class="text-sm">Nama<input name="name" value="{{ old('name', $customer->name) }}" required class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label></div>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm">Jenis Kelamin<select name="gender" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"><option value="">-</option><option value="L" @selected(old('gender', $customer->gender) === 'L')>Laki-laki</option><option value="P" @selected(old('gender', $customer->gender) === 'P')>Perempuan</option></select></label><label class="text-sm">Pekerjaan<input name="occupation" value="{{ old('occupation', $customer->occupation) }}" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label></div>
                    <label class="text-sm">Alamat<textarea name="address" rows="3" class="mt-1 w-full border border-[#c6d0d5] px-3 py-2">{{ old('address', $customer->address) }}</textarea></label>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm">Status<select name="status" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"><option value="ACTIVE" @selected($customer->status === 'ACTIVE')>Aktif</option><option value="INACTIVE" @selected($customer->status === 'INACTIVE')>Tidak aktif</option><option value="SUSPENDED" @selected($customer->status === 'SUSPENDED')>Ditangguhkan</option></select></label><label class="text-sm">Tanggal berhenti<input type="date" name="resigned_at" value="{{ optional($customer->resigned_at)->format('Y-m-d') }}" class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label></div>
                    <label class="text-sm">Alasan berhenti<textarea name="exit_reason" rows="2" class="mt-1 w-full border border-[#c6d0d5] px-3 py-2">{{ old('exit_reason', $customer->exit_reason) }}</textarea></label>
                    <button class="w-fit rounded bg-[#063c55] px-5 py-2 text-sm font-semibold text-white">Simpan Profil</button>
                </form>
            </section>

            <section class="border border-[#d5dfe3] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Pinjaman Anggota</h2><span class="text-xs text-[#66757d]">{{ $customer->loans->count() }} pinjaman</span></div>
                <div class="mt-4 space-y-3">
                    @forelse ($customer->loans as $loan)
                        <div class="border-t border-[#a7cedd] pt-3"><div class="flex items-start justify-between gap-4"><div><p class="font-semibold text-[#063c55]">Rp {{ number_format($loan->loan_amount, 0, ',', '.') }}</p><p class="mt-1 text-xs text-[#66757d]">{{ $loan->duration_days }} hari · bunga {{ $loan->interest_rate }}% · {{ $loan->collector?->name ?: 'Collector belum dipilih' }}</p></div><span class="rounded-full bg-[#fff1c7] px-3 py-1 text-xs font-semibold text-[#8a5a00]">{{ $loan->status }}</span></div><div class="mt-3 flex flex-wrap gap-2"><a href="{{ route('admin.customers.loans.show', [$customer, $loan]) }}" class="rounded bg-[#063c55] px-3 py-2 text-xs font-semibold text-white">Lihat kalender & detail</a><form method="POST" action="{{ route('admin.loans.destroy', $loan) }}">@csrf @method('DELETE')<button onclick="return confirm('Hapus pinjaman ini?')" class="rounded bg-[#d71920] px-3 py-2 text-xs text-white">Hapus</button></form></div></div>
                    @empty
                        <p class="py-8 text-center text-sm text-[#66757d]">Belum ada pinjaman.</p>
                    @endforelse
                </div>
                <details class="mt-5 border-t border-[#d5dfe3] pt-4"><summary class="cursor-pointer text-sm font-semibold text-[#063c55]">Tambah pinjaman</summary><form method="POST" action="{{ route('admin.customers.loans.store', $customer) }}" class="mt-4 grid gap-4 sm:grid-cols-2">@csrf<label class="text-sm">Collector<select name="collector_id" required class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"><option value="">Pilih collector</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></label><label class="text-sm">Nominal<input name="loan_amount" type="number" min="1000" required class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label><label class="text-sm">Durasi hari<input name="duration_days" type="number" min="1" max="365" value="60" required class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label><label class="text-sm">Bunga %<input name="interest_rate" type="number" min="0" max="100" step="0.01" value="0" required class="mt-1 h-10 w-full border border-[#c6d0d5] px-3"></label><button class="w-fit rounded bg-[#063c55] px-5 py-2 text-sm font-semibold text-white sm:col-span-2">Tambah Pinjaman</button></form></details>
            </section>
        </div>
    </main>
@endsection
