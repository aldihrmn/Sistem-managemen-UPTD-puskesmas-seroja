@extends('layouts.app')
@section('title', 'Jadwal Kerja')
@section('content')
@php
    $nav = fn ($d, $v = null, $sc = null) => route('pegawai.schedules.index', ['view' => $v ?? $view, 'date' => $d->toDateString(), 'scope' => $sc ?? $scope]);
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
        @foreach(['mine' => 'Jadwal Saya', 'team' => 'Jadwal Tim', 'history' => 'Riwayat'] as $k => $l)
            <a href="{{ $nav($date, $view, $k) }}" class="rounded-md px-3 py-1.5 {{ $scope === $k ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $l }}</a>
        @endforeach
    </div>
    @if($scope !== 'history')
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
            @foreach(['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan'] as $k => $l)
                <a href="{{ $nav($date, $k) }}" class="rounded-md px-3 py-1.5 {{ $view === $k ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $l }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ $nav($prev) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5">&larr;</a>
            <a href="{{ $nav(today()) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5">Hari ini</a>
            <a href="{{ $nav($next) }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5">&rarr;</a>
            <span class="ml-2 font-semibold">{{ $view === 'day' ? $date->translatedFormat('d F Y') : ($view === 'week' ? $from->translatedFormat('d M').' – '.$to->translatedFormat('d M Y') : $date->translatedFormat('F Y')) }}</span>
        </div>
    @endif
</div>

@if($scope === 'history')
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Jam</th><th class="px-5 py-3">Tugas / Lokasi</th><th class="px-5 py-3">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
            @forelse($history as $s)
                <tr><td class="px-5 py-3">{{ $s->work_date->translatedFormat('d M Y') }}</td><td class="px-5 py-3">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</td><td class="px-5 py-3 text-gray-600">{{ $s->shift?->name }}@if($s->location) · {{ $s->location }}@endif</td><td class="px-5 py-3"><x-badge :status="$s->status" /></td></tr>
            @empty
                <tr><td colspan="4"><x-empty>Belum ada riwayat jadwal.</x-empty></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>
@else
    <div class="space-y-4">
        @forelse($grouped as $day => $items)
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <h3 class="px-5 py-3 font-semibold border-b border-gray-100 {{ \Carbon\Carbon::parse($day)->isToday() ? 'text-emerald-700' : '' }}">{{ \Carbon\Carbon::parse($day)->translatedFormat('l, d F Y') }}</h3>
                @foreach($items as $s)
                    <div class="px-5 py-3 flex items-center justify-between text-sm border-b border-gray-50 last:border-0">
                        <div>
                            <div class="font-medium">{{ $scope === 'team' ? $s->employee->full_name : ($s->shift?->name ?? 'Jadwal') }}</div>
                            <div class="text-xs text-gray-500">{{ $s->location }}{{ $s->notes ? ' · '.$s->notes : '' }}</div>
                        </div>
                        <div class="text-gray-700 whitespace-nowrap">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</div>
                    </div>
                @endforeach
            </section>
        @empty
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm"><x-empty>Tidak ada jadwal pada periode ini.</x-empty></div>
        @endforelse
    </div>
@endif
@endsection
