@extends('layouts.app')
@section('title', 'Detail Jadwal')
@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-start justify-between">
        <div><h2 class="text-lg font-semibold">{{ $schedule->employee->full_name }}</h2><p class="text-sm text-gray-500">{{ $schedule->employee->department?->name }} · {{ $schedule->employee->profession }}</p></div>
        <x-badge :status="$schedule->status" />
    </div>
    <dl class="mt-5 grid gap-4 sm:grid-cols-2 text-sm">
        @foreach([
            'Tanggal' => $schedule->work_date->translatedFormat('l, d F Y'),
            'Jam' => substr($schedule->start_time, 0, 5).' – '.substr($schedule->end_time, 0, 5).($schedule->crosses_midnight ? ' (hingga esok hari)' : ''),
            'Jenis Jadwal' => $schedule->shift?->name, 'Lokasi / Layanan' => $schedule->location, 'Catatan' => $schedule->notes,
            'Dibuat oleh' => $schedule->creator?->name,
            'Absensi' => $attendance ? ucfirst($attendance->status).($attendance->check_in ? ' · masuk '.substr($attendance->check_in, 0, 5) : '').($attendance->check_out ? ' · pulang '.substr($attendance->check_out, 0, 5) : '') : 'Belum tercatat',
        ] as $l => $v)<div><dt class="text-gray-500">{{ $l }}</dt><dd class="font-medium">{{ $v ?: '-' }}</dd></div>@endforeach
    </dl>
    <div class="mt-6 flex gap-2">
        <a href="{{ route('admin.schedules.index', ['date' => $schedule->work_date->toDateString()]) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Kembali</a>
        @if($schedule->status === 'aktif')
            <a href="{{ route('admin.schedules.edit', $schedule) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700">Edit</a>
            <form method="POST" action="{{ route('admin.schedules.cancel', $schedule) }}" onsubmit="return confirm('Batalkan jadwal ini?')">@csrf @method('PATCH')<button class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Batalkan Jadwal</button></form>
        @endif
    </div>
</div>
@endsection
