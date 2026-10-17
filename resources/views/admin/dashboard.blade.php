@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<p class="text-sm text-gray-500 mb-5">{{ now()->translatedFormat('l, d F Y') }}</p>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <x-stat-card label="Total Pegawai" :value="$totalEmployees" icon="users" :href="route('admin.employees.index')" />
    <x-stat-card label="Pegawai Aktif" :value="$activeEmployees" icon="users" :href="route('admin.employees.index', ['status' => 'aktif'])" />
    <x-stat-card label="Terjadwal Hari Ini" :value="$scheduledToday" icon="calendar" :href="route('admin.schedules.index')" />
    <x-stat-card label="Terlambat Hari Ini" :value="$attendance['terlambat'] ?? 0" icon="clock" :href="route('admin.attendance.index', ['status' => 'terlambat'])" />
    <x-stat-card label="Izin / Sakit / Cuti Hari Ini" :value="($attendance['izin'] ?? 0) + ($attendance['sakit'] ?? 0) + ($attendance['cuti'] ?? 0)" icon="clipboard" />
    <x-stat-card label="Pengajuan Jadwal Menunggu" :value="$pendingRequests" icon="clipboard" :href="route('admin.schedule-requests.index', ['status' => 'menunggu'])" />
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Jadwal Kerja Hari Ini</h2>
        @forelse($todaySchedules as $s)
            <div class="px-5 py-3 flex items-center justify-between border-b border-gray-50 last:border-0">
                <div>
                    <div class="font-medium text-sm">{{ $s->employee->full_name }}</div>
                    <div class="text-xs text-gray-500">{{ $s->employee->department?->name ?? '-' }} · {{ $s->location ?? $s->shift?->name ?? 'Jadwal' }}</div>
                </div>
                <div class="text-sm text-gray-600">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</div>
            </div>
        @empty
            <x-empty>Belum ada jadwal hari ini.</x-empty>
        @endforelse
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Rekap Kehadiran Hari Ini</h2>
        <div class="p-5 space-y-2 text-sm">
            @foreach(['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpa'] as $st)
                <div class="flex items-center justify-between"><x-badge :status="$st" /><span class="font-medium">{{ $attendance[$st] ?? 0 }}</span></div>
            @endforeach
        </div>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Kehadiran 6 Bulan Terakhir</h2>
        @php $max = max(1, $chart->max('count')); @endphp
        <div class="p-5">
            @if($chart->sum('count') === 0)
                <x-empty>Belum ada data kehadiran.</x-empty>
            @else
                <div class="flex items-end gap-3 h-40">
                    @foreach($chart as $c)
                        <div class="flex-1 flex flex-col items-center justify-end h-full">
                            <span class="text-xs text-gray-500 mb-1">{{ $c['count'] }}</span>
                            <div class="w-full rounded-t-md bg-emerald-500" style="height: {{ max(2, round($c['count'] / $max * 100)) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-3 mt-2">@foreach($chart as $c)<div class="flex-1 text-center text-xs text-gray-500">{{ $c['label'] }}</div>@endforeach</div>
            @endif
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Pegawai Aktif per Unit Kerja</h2>
        @forelse($departments as $d)
            <div class="px-5 py-3 flex justify-between text-sm border-b border-gray-50 last:border-0"><span>{{ $d->name }}</span><span class="font-medium">{{ $d->employees_count }}</span></div>
        @empty
            <x-empty>Belum ada unit kerja.</x-empty>
        @endforelse
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Pengajuan Terbaru</h2>
        @forelse($latestRequests as $r)
            <div class="px-5 py-3 flex items-center justify-between text-sm border-b border-gray-50 last:border-0">
                <span>{{ $r->employee->full_name }}</span><x-badge :status="$r->status" />
            </div>
        @empty
            <x-empty>Belum ada pengajuan.</x-empty>
        @endforelse
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Aktivitas Terbaru</h2>
        @forelse($activities as $a)
            <div class="px-5 py-3 text-sm border-b border-gray-50 last:border-0">
                <div>{{ $a->description }}</div>
                <div class="text-xs text-gray-400">{{ $a->user?->name ?? 'Sistem' }} · {{ $a->created_at->diffForHumans() }}</div>
            </div>
        @empty
            <x-empty>Belum ada aktivitas.</x-empty>
        @endforelse
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
        <h2 class="px-5 py-4 font-semibold border-b border-gray-100">Pengumuman</h2>
        @forelse($announcements as $n)
            <div class="px-5 py-3 text-sm border-b border-gray-50 last:border-0">
                <div class="font-medium">{{ $n->title }}</div>
                <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($n->body, 90) }}</div>
            </div>
        @empty
            <x-empty>Belum ada pengumuman.</x-empty>
        @endforelse
    </section>
</div>
@endsection
