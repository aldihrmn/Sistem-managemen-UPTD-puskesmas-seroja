@extends('layouts.app')
@section('title', 'Buat Pengajuan Jadwal')
@section('content')
@php $fmt = fn ($s) => $s->work_date->translatedFormat('D, d M Y').' · '.substr($s->start_time, 0, 5).'–'.substr($s->end_time, 0, 5).($s->location ? ' · '.$s->location : ''); @endphp
<form method="POST" action="{{ route('pegawai.schedule-requests.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-2xl">
    @csrf
    <p class="mb-4 text-sm text-gray-500">Jadwal resmi tidak berubah sampai pengajuan Anda disetujui admin.</p>
    <div class="grid gap-4">
        <x-select name="type" label="Jenis Pengajuan" :options="\App\Models\ScheduleRequest::TYPES" />

        <div id="sec-schedule" class="sec hidden">
            <x-select name="schedule_id" label="Jadwal Saya" :options="$mySchedules->mapWithKeys(fn ($s) => [$s->id => $fmt($s)])" />
            @if($mySchedules->isEmpty())<p class="mt-1 text-xs text-amber-600">Anda belum memiliki jadwal aktif dalam 60 hari ke depan.</p>@endif
        </div>

        <div id="sec-target" class="sec hidden">
            <x-select name="target_schedule_id" label="Tukar dengan jadwal pegawai lain" :options="$otherSchedules->mapWithKeys(fn ($s) => [$s->id => $s->employee->full_name.' — '.$fmt($s)])" />
            @if($otherSchedules->isEmpty())<p class="mt-1 text-xs text-amber-600">Belum ada jadwal pegawai lain di unit Anda yang dapat ditukar.</p>@endif
        </div>

        <div id="sec-new" class="sec hidden grid gap-4 sm:grid-cols-2">
            <x-field name="requested_date" type="date" label="Tanggal yang diminta" class="sm:col-span-2" />
            <x-field name="requested_start" type="time" label="Jam mulai" />
            <x-field name="requested_end" type="time" label="Jam selesai" />
            <x-select name="requested_shift_id" label="Jenis jadwal (opsional)" :options="$shifts->pluck('name', 'id')" placeholder="— Tidak ditentukan —" class="sm:col-span-2" />
        </div>

        <div>
            <label for="reason" class="block text-sm font-medium mb-1">Alasan <span class="text-red-500">*</span></label>
            <textarea id="reason" name="reason" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm {{ $errors->has('reason') ? 'border-red-400' : 'border-gray-300' }}">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="attachment" class="block text-sm font-medium mb-1">Lampiran (opsional)</label>
            <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" class="text-sm">
            @error('attachment')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="mt-6 flex gap-2">
        <button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">Kirim Pengajuan</button>
        <a href="{{ route('pegawai.schedule-requests.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm hover:bg-gray-50">Batal</a>
    </div>
</form>
<script>
    const map = { ubah: ['sec-schedule', 'sec-new'], minta: ['sec-new'], tukar: ['sec-schedule', 'sec-target'], batal: ['sec-schedule'] };
    const typeEl = document.getElementById('type');
    function upd() {
        document.querySelectorAll('.sec').forEach(e => e.classList.add('hidden'));
        (map[typeEl.value] || []).forEach(id => document.getElementById(id).classList.remove('hidden'));
    }
    typeEl.addEventListener('change', upd); upd();
</script>
@endsection
