@extends('layouts.member')

@section('title', 'Profil Anggota')

@section('content')
    <div class="max-w-2xl">
        <a href="{{ route('member.dashboard') }}" class="text-sm font-medium text-[#07516f]">&larr; Beranda</a>
        <h1 class="mt-3 text-2xl font-semibold text-[#063c55]">Detail informasi</h1>
        <p class="mt-1 text-sm text-[#66757d]">Perbarui informasi pribadi yang digunakan koperasi.</p>

        @if (session('success'))<div class="mt-5 border-l-4 border-[#0b6b4f] bg-[#e8f5ef] px-4 py-3 text-sm text-[#0b6b4f]" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="mt-5 border-l-4 border-[#d71920] bg-[#fff1f1] px-4 py-3 text-sm text-[#9f1239]" role="alert">{{ $errors->first() }}</div>@endif

        <section class="mt-5 border border-[#d5dfe3] bg-white p-4 sm:p-6">
            <form method="POST" action="{{ route('member.profile.update') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <label class="text-sm font-medium">Nama lengkap<input name="name" value="{{ old('name', $customer->name) }}" required maxlength="255" autocomplete="name" class="mt-1 min-h-11 w-full border border-[#c6d0d5] px-3 font-normal"></label>
                <label class="text-sm font-medium">Nomor telepon<input name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="30" autocomplete="tel" class="mt-1 min-h-11 w-full border border-[#c6d0d5] px-3 font-normal"></label>
                <label class="text-sm font-medium">Jenis kelamin<select name="gender" class="mt-1 min-h-11 w-full border border-[#c6d0d5] bg-white px-3 font-normal"><option value="">Pilih</option><option value="L" @selected(old('gender', $customer->gender) === 'L')>Laki-laki</option><option value="P" @selected(old('gender', $customer->gender) === 'P')>Perempuan</option></select></label>
                <label class="text-sm font-medium">Pekerjaan<input name="occupation" value="{{ old('occupation', $customer->occupation) }}" maxlength="150" class="mt-1 min-h-11 w-full border border-[#c6d0d5] px-3 font-normal"></label>
                <label class="text-sm font-medium sm:col-span-2">Usaha<input name="business_name" value="{{ old('business_name', $customer->business_name) }}" maxlength="255" class="mt-1 min-h-11 w-full border border-[#c6d0d5] px-3 font-normal"></label>
                <label class="text-sm font-medium sm:col-span-2">Alamat<textarea name="address" rows="3" maxlength="2000" autocomplete="street-address" class="mt-1 w-full border border-[#c6d0d5] px-3 py-2 font-normal">{{ old('address', $customer->address) }}</textarea></label>
                <div class="sm:col-span-2"><p class="text-xs text-[#66757d]">ID anggota: {{ $customer->member_code }}. Hubungi petugas untuk perubahan ID atau status keanggotaan.</p><button class="mt-4 min-h-11 rounded bg-[#063c55] px-5 text-sm font-semibold text-white hover:bg-[#07516f]">Simpan perubahan</button></div>
            </form>
        </section>
    </div>
@endsection