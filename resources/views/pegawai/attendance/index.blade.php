@extends('layouts.app')
@section('title', 'Absensi')
@section('content')
<section class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">
    <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Absensi Hari Ini</h2>
    @forelse($todaySchedules as $s)
        @php $a = $s->attendance; @endphp
        <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-50 last:border-0">
            <div><div class="font-medium text-sm">{{ $s->shift?->name ?? 'Jadwal' }}{{ $s->location ? ' · '.$s->location : '' }}</div><div class="text-xs text-gray-500">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</div></div>
            <div class="flex items-center gap-3">
                @if($a)
                    <x-badge :status="$a->status" />
                    <span class="text-xs text-gray-500">Masuk {{ $a->check_in ? substr($a->check_in, 0, 5) : '-' }} · Pulang {{ $a->check_out ? substr($a->check_out, 0, 5) : '-' }}</span>
                    @if($a->check_in && ! $a->check_out)
                        <form method="POST" action="{{ route('pegawai.attendance.check-out', $s) }}">@csrf<button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Absen Pulang</button></form>
                    @endif
                @else
                    <form method="POST" action="{{ route('pegawai.attendance.check-in', $s) }}">@csrf<button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Absen Masuk</button></form>
                @endif
            </div>
        </div>
    @empty
        <x-empty>Tidak ada jadwal hari ini, sehingga absensi tidak tersedia.</x-empty>
    @endforelse
    <p class="px-5 py-3 text-xs text-gray-400 border-t border-gray-50">Absensi dicatat manual melalui tombol ini (belum terhubung fingerprint, GPS, atau pengenalan wajah).</p>
</section>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form method="GET" class="flex items-center gap-2"><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Tampilkan</button></form>
    <div class="flex flex-wrap gap-3 text-sm">@foreach(['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpa'] as $st)<div class="flex items-center gap-1"><x-badge :status="$st" /><span class="font-medium">{{ $summary[$st] ?? 0 }}</span></div>@endforeach</div>
</div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Masuk</th><th class="px-5 py-3">Pulang</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Catatan</th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($records as $r)
            <tr><td class="px-5 py-3">{{ $r->work_date->translatedFormat('d M Y') }}</td><td class="px-5 py-3">{{ $r->check_in ? substr($r->check_in, 0, 5) : '-' }}</td><td class="px-5 py-3">{{ $r->check_out ? substr($r->check_out, 0, 5) : '-' }}</td><td class="px-5 py-3"><x-badge :status="$r->status" /></td><td class="px-5 py-3 text-gray-500">{{ $r->notes }}</td></tr>
        @empty
            <tr><td colspan="5"><x-empty>Belum ada catatan absensi pada bulan ini.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
