@extends('layouts.app')
@section('title', 'Profil Saya')
@section('content')
<form method="POST" action="{{ route('pegawai.profile.update') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-2xl">
    @csrf @method('PUT')
    <div class="mb-5 text-sm text-gray-600">
        <div class="text-lg font-semibold text-gray-800">{{ $employee->full_name }}</div>
        {{ $employee->position?->name ?? '-' }} · {{ $employee->department?->name ?? '-' }} · NIP: {{ $employee->nip ?: '-' }}
        <div class="text-xs text-gray-400 mt-1">Data kepegawaian lain hanya dapat diubah oleh admin.</div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="phone" label="Nomor Telepon" :value="$employee->phone" />
        <x-field name="address" label="Alamat" :value="$employee->address" />
        <div class="sm:col-span-2"><label for="photo" class="block text-sm font-medium mb-1">Foto Profil</label><input id="photo" name="photo" type="file" accept="image/*" class="text-sm">@error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    </div>
    <div class="mt-6 border-t border-gray-100 pt-5">
        <h3 class="font-semibold mb-2">Ganti Password <span class="text-sm font-normal text-gray-500">(opsional)</span></h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-field name="current_password" type="password" label="Password saat ini" autocomplete="current-password" />
            <x-field name="password" type="password" label="Password baru" autocomplete="new-password" />
            <x-field name="password_confirmation" type="password" label="Konfirmasi" autocomplete="new-password" />
        </div>
    </div>
    <div class="mt-6"><button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button></div>
</form>
@endsection
