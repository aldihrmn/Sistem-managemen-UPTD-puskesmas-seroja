@extends('layouts.app')
@section('title', $label)
@section('content')
<div class="mb-4 inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
    @foreach([null => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'] as $k => $l)
        <a href="{{ route('admin.approvals.index', [$type] + ($k ? ['status' => $k] : [])) }}" class="rounded-md px-3 py-1.5 {{ ($status ?: null) === ($k ?: null) ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $l }}</a>
    @endforeach
</div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Pegawai</th><th class="px-5 py-3">{{ $type === 'leave' ? 'Jenis & Periode' : 'Tanggal & Jam' }}</th><th class="px-5 py-3">Alasan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Proses</th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($items as $i)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $i->employee->full_name }}</td>
                <td class="px-5 py-3">
                    @if($type === 'leave')
                        <x-badge :status="$i->type" /> <span class="ml-1">{{ $i->start_date->format('d/m/Y') }} – {{ $i->end_date->format('d/m/Y') }}</span>
                        @if($i->attachment)<div><a class="text-xs text-emerald-700 hover:underline" href="{{ route('attachments.show', ['leave', $i->id]) }}">Lampiran</a></div>@endif
                    @else
                        {{ $i->work_date->format('d/m/Y') }} · {{ substr($i->start_time, 0, 5) }}–{{ substr($i->end_time, 0, 5) }}
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-600 max-w-xs">{{ $i->reason }}</td>
                <td class="px-5 py-3"><x-badge :status="$i->status" /></td>
                <td class="px-5 py-3">
                    @if($i->status === 'menunggu')
                        <form method="POST" action="{{ route('admin.approvals.approve', [$type, $i->id]) }}" class="mb-2 flex gap-2" onsubmit="return confirm('Setujui pengajuan ini?')">
                            @csrf @method('PATCH')
                            <input name="decision_note" placeholder="Catatan (opsional)" class="rounded-lg border border-gray-300 px-2 py-1 text-xs w-36">
                            <button class="rounded-lg bg-emerald-600 px-3 py-1 text-xs text-white">Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('admin.approvals.reject', [$type, $i->id]) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <input name="decision_note" placeholder="Alasan penolakan" required class="rounded-lg border border-gray-300 px-2 py-1 text-xs w-36">
                            <button class="rounded-lg bg-red-600 px-3 py-1 text-xs text-white">Tolak</button>
                        </form>
                    @else
                        <div class="text-xs text-gray-500">{{ $i->processor?->name }} · {{ $i->processed_at?->format('d/m/Y H:i') }}@if($i->decision_note)<br>{{ $i->decision_note }}@endif</div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty>Belum ada pengajuan.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@if($errors->any())<p class="mt-2 text-sm text-red-600">{{ $errors->first() }}</p>@endif
@endsection
