@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@if(! $employee)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 max-w-xl">
        <h2 class="text-lg font-semibold">Halo, {{ auth()->user()->name }}</h2>
        <p class="mt-2 text-sm text-gray-600">Akun Anda belum dihubungkan dengan data pegawai. Silakan hubungi admin.</p>
    </div>
@else
    <div class="mb-5">
        <h2 class="text-xl font-semibold">Halo, {{ $employee->full_name }}</h2>
        <p class="text-sm text-gray-500">{{ now()->translatedFormat('l, d F Y') }} · {{ $employee->department?->name ?? 'Unit belum diatur' }}@if($employee->position) · {{ $employee->position->name }}@endif</p>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('pegawai.schedule-requests.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Ajukan Perubahan Jadwal</a>
        <a href="{{ route('pegawai.requests.create', 'leave') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">Ajukan Izin / Cuti</a>
        <a href="{{ route('pegawai.attendance.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">Absensi</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="text-sm text-gray-500">Status Kehadiran Hari Ini</div>
            <div class="mt-2">@if($attendanceToday)<x-badge :status="$attendanceToday->status" />@else<span class="text-sm text-gray-400">Belum tercatat</span>@endif</div>
        </div>
        <x-stat-card label="Pengajuan Menunggu" :value="$pendingCount" icon="clipboard" />
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="text-sm text-gray-500">Pengajuan Jadwal Terakhir</div>
            <div class="mt-2">@if($lastRequest)<x-badge :status="$lastRequest->status" />@else<span class="text-sm text-gray-400">Belum ada</span>@endif</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="px-5 py-4 font-semibold border-b border-gray-100">Jadwal Hari Ini</h3>
            @forelse($todaySchedules as $s)
                <div class="px-5 py-3 flex justify-between text-sm border-b border-gray-50 last:border-0">
                    <span>{{ $s->location ?? $s->shift?->name ?? 'Jadwal' }}</span>
                    <span class="text-gray-600">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</span>
                </div>
            @empty
                <x-empty>Tidak ada jadwal hari ini.</x-empty>
            @endforelse
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="px-5 py-4 font-semibold border-b border-gray-100">7 Hari Berikutnya</h3>
            @forelse($upcoming as $s)
                <div class="px-5 py-3 flex justify-between text-sm border-b border-gray-50 last:border-0">
                    <span>{{ $s->work_date->translatedFormat('D, d M') }} · {{ $s->location ?? $s->shift?->name ?? 'Jadwal' }}</span>
                    <span class="text-gray-600">{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</span>
                </div>
            @empty
                <x-empty>Belum ada jadwal dalam 7 hari ke depan.</x-empty>
            @endforelse
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="px-5 py-4 font-semibold border-b border-gray-100">Rekap Kehadiran Bulan Ini</h3>
            <div class="p-5 grid grid-cols-3 gap-3 text-center text-sm">
                @foreach(['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpa'] as $st)
                    <div><div class="text-xl font-semibold">{{ $monthSummary[$st] ?? 0 }}</div><div class="text-gray-500">{{ ucfirst($st) }}</div></div>
                @endforeach
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="px-5 py-4 font-semibold border-b border-gray-100">Pengumuman</h3>
            @forelse($announcements as $n)
                <div class="px-5 py-3 text-sm border-b border-gray-50 last:border-0">
                    <div class="font-medium">{{ $n->title }}</div>
                    <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($n->body, 100) }}</div>
                </div>
            @empty
                <x-empty>Belum ada pengumuman.</x-empty>
            @endforelse
        </section>
    </div>
@endif
@endsection
