@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
<div class="max-w-3xl space-y-6">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold mb-3">Konfigurasi Dasar</h2>
        <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf @method('PUT')
            <x-field name="institution_name" label="Nama Instansi" :value="$institution" required />
            <x-field name="late_tolerance_minutes" type="number" label="Toleransi keterlambatan (menit)" :value="$tolerance" min="0" max="240" required />
            <div class="sm:col-span-2"><button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button></div>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-semibold mb-1">Hari Libur</h2>
        <p class="text-sm text-gray-500 mb-3">Hari libur tidak dihitung sebagai hari kerja pada laporan bulanan.</p>
        <form method="POST" action="{{ route('admin.settings.holidays.store') }}" class="grid gap-3 sm:grid-cols-3 items-start">
            @csrf
            <x-field name="date" type="date" label="Tanggal" required />
            <x-field name="name" label="Keterangan" required />
            <div class="sm:pt-6"><button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Tambah</button></div>
        </form>
        <div class="mt-4 divide-y divide-gray-50 text-sm">
            @forelse($holidays as $h)
                <div class="py-2 flex items-center justify-between"><span>{{ $h->date->translatedFormat('d F Y') }} — {{ $h->name }}</span>
                    <form method="POST" action="{{ route('admin.settings.holidays.destroy', $h) }}" onsubmit="return confirm('Hapus hari libur ini?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Hapus</button></form></div>
            @empty
                <x-empty>Belum ada hari libur terdaftar.</x-empty>
            @endforelse
        </div>
    </section>
</div>
@endsection
