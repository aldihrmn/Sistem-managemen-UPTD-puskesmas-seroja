@extends('layouts.app')
@section('title', 'Jenis Jadwal')
@section('content')
<div class="max-w-5xl space-y-6">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold mb-3">Tambah Jenis Jadwal</h2>
        <form method="POST" action="{{ route('admin.shifts.store') }}" class="grid gap-3 sm:grid-cols-6 items-start">
            @csrf
            <x-field name="name" label="Nama" required class="sm:col-span-2" />
            <x-select name="type" label="Tipe" :options="$types" value="reguler" placeholder="—" />
            <x-field name="start_time" type="time" label="Mulai" value="08:00" />
            <x-field name="end_time" type="time" label="Selesai" value="15:00" />
            <x-field name="color" type="color" label="Warna" value="#16a34a" />
            <label class="sm:col-span-4 inline-flex items-center gap-2 text-sm"><input type="hidden" name="crosses_midnight" value="0"><input type="checkbox" name="crosses_midnight" value="1" class="rounded border-gray-300 text-emerald-600"> Lintas tengah malam</label>
            <div class="sm:col-span-2 text-right"><button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button></div>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        @forelse($shifts as $sh)
            <div class="p-4 flex flex-wrap items-end gap-3">
                <form id="s{{ $sh->id }}" method="POST" action="{{ route('admin.shifts.update', $sh) }}" class="flex flex-wrap items-end gap-3 flex-1">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $sh->name }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm w-48">
                    <select name="type" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm bg-white">@foreach($types as $k => $l)<option value="{{ $k }}" @selected($sh->type === $k)>{{ $l }}</option>@endforeach</select>
                    <input name="start_time" type="time" value="{{ substr($sh->start_time, 0, 5) }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    <input name="end_time" type="time" value="{{ substr($sh->end_time, 0, 5) }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    <input name="color" type="color" value="{{ $sh->color }}" class="h-8 w-10">
                    <input type="hidden" name="crosses_midnight" value="0">
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="crosses_midnight" value="1" @checked($sh->crosses_midnight)> +1 hari</label>
                </form>
                <x-badge :status="$sh->is_active ? 'aktif' : 'nonaktif'" />
                <button form="s{{ $sh->id }}" class="text-sm text-emerald-700 hover:underline">Simpan</button>
                <form method="POST" action="{{ route('admin.shifts.toggle', $sh) }}" onsubmit="return confirm('Ubah status jenis jadwal ini?')">@csrf @method('PATCH')<button class="text-sm text-gray-600 hover:underline">{{ $sh->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
            </div>
        @empty
            <x-empty>Belum ada jenis jadwal.</x-empty>
        @endforelse
    </section>
    @if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
</div>
@endsection
