@extends('layouts.app')
@section('title', 'Penjadwalan')
@section('content')
@php
    $keep = request()->only(['department_id', 'employee_id', 'shift_id', 'q']);
    $nav = fn ($d, $v = null) => route('admin.schedules.index', array_merge($keep, ['view' => $v ?? $view, 'date' => $d->toDateString()]));
    $label = match ($view) {
        'day' => $date->translatedFormat('l, d F Y'),
        'week' => $from->translatedFormat('d M').' – '.$to->translatedFormat('d M Y'),
        default => $date->translatedFormat('F Y'),
    };
    $active = $schedules->where('status', 'aktif');
    $byDate = $active->groupBy(fn ($s) => $s->work_date->toDateString());
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
        @foreach(['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan'] as $v => $l)
            <a href="{{ $nav($date, $v) }}" class="rounded-md px-3 py-1.5 {{ $view === $v ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $l }}</a>
        @endforeach
    </div>
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ $nav($prev) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 hover:bg-gray-50">&larr;</a>
        <a href="{{ $nav(today()) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 hover:bg-gray-50">Hari ini</a>
        <a href="{{ $nav($next) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 hover:bg-gray-50">&rarr;</a>
        <span class="ml-2 font-semibold">{{ $label }}</span>
    </div>
    <a href="{{ route('admin.schedules.create', ['date' => $date->toDateString()]) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"><x-icon name="plus" class="w-4 h-4" /> Tambah Jadwal</a>
</div>

<form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
    <input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="date" value="{{ $date->toDateString() }}">
    <div><label class="block text-xs text-gray-500 mb-1">Cari nama pegawai</label><input name="q" value="{{ request('q') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    @foreach([['department_id', 'Unit Kerja', $departments], ['employee_id', 'Pegawai', $employees], ['shift_id', 'Jenis Tugas', $shifts]] as [$n, $l, $o])
        <div><label class="block text-xs text-gray-500 mb-1">{{ $l }}</label>
            <select name="{{ $n }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>
                @foreach($o as $id => $name)<option value="{{ $id }}" @selected(request($n) == $id)>{{ $name }}</option>@endforeach</select></div>
    @endforeach
    <div class="flex gap-2"><button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Terapkan</button><a href="{{ route('admin.schedules.index', ['view' => $view, 'date' => $date->toDateString()]) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Reset</a></div>
</form>

<div class="mt-4">
@if($view === 'day')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Pegawai</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Jam</th><th class="px-5 py-3">Tugas / Lokasi</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-50">
            @forelse($schedules as $s)
                <tr class="{{ $s->status === 'dibatalkan' ? 'opacity-60' : '' }}">
                    <td class="px-5 py-3"><div class="font-medium">{{ $s->employee->full_name }}</div><div class="text-xs text-gray-500">{{ $s->employee->profession }}</div></td>
                    <td class="px-5 py-3 text-gray-600">{{ $s->employee->department?->name ?? '-' }}</td>
                    <td class="px-5 py-3 whitespace-nowrap">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}@if($s->crosses_midnight) <span class="text-xs text-gray-400">(+1)</span>@endif</td>
                    <td class="px-5 py-3 text-gray-600">{{ $s->shift?->name }}@if($s->location) · {{ $s->location }}@endif</td>
                    <td class="px-5 py-3"><x-badge :status="$s->status" /></td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.schedules.show', $s) }}" class="text-emerald-700 hover:underline">Detail</a>
                        @if($s->status === 'aktif')
                            <a href="{{ route('admin.schedules.edit', $s) }}" class="ml-3 text-gray-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.schedules.cancel', $s) }}" class="inline" onsubmit="return confirm('Batalkan jadwal ini?')">@csrf @method('PATCH')<button class="ml-3 text-red-600 hover:underline">Batalkan</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty>Belum ada jadwal pada tanggal ini.</x-empty></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

@elseif($view === 'week')
    @php $byEmp = $active->groupBy('employee_id'); @endphp
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Pegawai</th>
                @foreach($days as $d)<th class="px-3 py-3 text-left {{ $d->isToday() ? 'text-emerald-700' : '' }}"><a href="{{ $nav($d, 'day') }}">{{ $d->translatedFormat('D d/m') }}</a></th>@endforeach</tr></thead>
            <tbody class="divide-y divide-gray-50">
            @forelse($byEmp as $list)
                <tr>
                    <td class="px-4 py-3 font-medium whitespace-nowrap">{{ $list->first()->employee->full_name }}</td>
                    @foreach($days as $d)
                        <td class="px-3 py-2 align-top">
                            @foreach($list->filter(fn ($s) => $s->work_date->isSameDay($d)) as $s)
                                @php $c = $s->shift?->color ?? '#16a34a'; @endphp
                                <a href="{{ route('admin.schedules.show', $s) }}" class="block mb-1 rounded px-2 py-1 text-xs whitespace-nowrap" style="background: {{ $c }}1a; color: {{ $c }}">{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</a>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="8"><x-empty>Belum ada jadwal pada minggu ini.</x-empty></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

@else
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="grid grid-cols-7 bg-gray-50 text-center text-xs uppercase text-gray-500">
            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h)<div class="py-2">{{ $h }}</div>@endforeach
        </div>
        <div class="grid grid-cols-7">
            @foreach($days as $d)
                @php $items = $byDate->get($d->toDateString(), collect()); @endphp
                <a href="{{ $nav($d, 'day') }}" class="min-h-24 border-t border-l border-gray-100 p-2 text-xs hover:bg-emerald-50/40 {{ $d->month !== $date->month ? 'bg-gray-50 text-gray-400' : '' }}">
                    <div class="font-medium {{ $d->isToday() ? 'text-emerald-700' : '' }}">{{ $d->day }}</div>
                    @if($items->count())
                        <div class="mt-1 inline-block rounded-full bg-emerald-100 px-2 text-emerald-700">{{ $items->count() }} jadwal</div>
                        @foreach($items->take(2) as $s)<div class="truncate mt-0.5">{{ $s->employee->full_name }}</div>@endforeach
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
</div>
@endsection
