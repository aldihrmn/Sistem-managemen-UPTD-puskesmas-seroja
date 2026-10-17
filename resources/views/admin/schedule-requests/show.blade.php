@extends('layouts.app')
@section('title', 'Detail Pengajuan Jadwal')
@section('content')
@php
    $fmt = fn ($s) => $s ? $s->work_date->translatedFormat('d M Y').' · '.substr($s->start_time, 0, 5).'–'.substr($s->end_time, 0, 5).($s->location ? ' · '.$s->location : '') : '-';
@endphp
<div class="max-w-3xl space-y-6">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between">
            <div><h2 class="text-lg font-semibold">{{ \App\Models\ScheduleRequest::TYPES[$r->type] }}</h2><p class="text-sm text-gray-500">{{ $r->employee->full_name }} · {{ $r->employee->department?->name }} · {{ $r->created_at->translatedFormat('d M Y H:i') }}</p></div>
            <x-badge :status="$r->status" />
        </div>
        <dl class="mt-5 grid gap-4 text-sm">
            <div><dt class="text-gray-500">Alasan</dt><dd class="font-medium whitespace-pre-line">{{ $r->reason }}</dd></div>
            @if($r->schedule)<div><dt class="text-gray-500">Jadwal awal</dt><dd class="font-medium">{{ $fmt($r->schedule) }}</dd></div>@endif
            @if(in_array($r->type, ['ubah', 'minta']))
                <div><dt class="text-gray-500">Jadwal yang diminta</dt><dd class="font-medium">{{ $r->requested_date?->translatedFormat('d M Y') }} · {{ substr($r->requested_start, 0, 5) }}–{{ substr($r->requested_end, 0, 5) }}</dd></div>
            @endif
            @if($r->type === 'tukar')
                <div><dt class="text-gray-500">Ditukar dengan ({{ $r->targetEmployee?->full_name }})</dt><dd class="font-medium">{{ $fmt($r->targetSchedule) }}</dd></div>
            @endif
            @if($r->attachment)<div><dt class="text-gray-500">Lampiran</dt><dd><a class="text-emerald-700 hover:underline" href="{{ route('attachments.show', ['schedule', $r->id]) }}">Unduh lampiran</a></dd></div>@endif
        </dl>
    </section>

    @if($r->status === 'menunggu')
        @if($problems)
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
                <div class="font-semibold mb-1">Benturan jadwal ditemukan — pengajuan belum dapat disetujui.</div>
                <ul class="list-disc pl-5">@foreach($problems as $p)<li>{{ $p }}</li>@endforeach</ul>
                <p class="mt-2">Selesaikan benturan terlebih dahulu (ubah/batalkan jadwal yang berbenturan) atau tolak pengajuan.</p>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">Tidak ada benturan jadwal. Pengajuan dapat disetujui.</div>
        @endif

        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 grid gap-6 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.schedule-requests.approve', $r) }}" class="space-y-3" onsubmit="return confirm('Setujui pengajuan ini? Jadwal resmi akan berubah.')">
                @csrf @method('PATCH')
                <x-field name="decision_note" label="Catatan (opsional)" />
                <button @disabled($problems) class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed">Setujui</button>
            </form>
            <form method="POST" action="{{ route('admin.schedule-requests.reject', $r) }}" class="space-y-3">
                @csrf @method('PATCH')
                <x-field name="decision_note" label="Alasan penolakan" required />
                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Tolak</button>
            </form>
        </section>
    @else
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-sm space-y-2">
            <div>Diproses oleh <strong>{{ $r->processor?->name ?? '-' }}</strong> pada {{ $r->processed_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
            <div>Catatan: {{ $r->decision_note ?: '-' }}</div>
            @if($r->before_snapshot || $r->after_snapshot)
                <details class="mt-2"><summary class="cursor-pointer text-emerald-700">Jadwal sebelum &amp; sesudah</summary>
                    <pre class="mt-2 rounded-lg bg-gray-50 p-3 text-xs overflow-x-auto">{{ json_encode(['sebelum' => $r->before_snapshot, 'sesudah' => $r->after_snapshot], JSON_PRETTY_PRINT) }}</pre></details>
            @endif
        </section>
    @endif
    <a href="{{ route('admin.schedule-requests.index') }}" class="inline-block text-sm text-gray-600 hover:underline">&larr; Kembali ke daftar</a>
</div>
@endsection
