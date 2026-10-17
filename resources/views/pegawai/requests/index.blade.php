@extends('layouts.app')
@section('title', $label)
@section('content')
<div class="mb-4 flex justify-end"><a href="{{ route('pegawai.requests.create', $type) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"><x-icon name="plus" class="w-4 h-4" /> Ajukan {{ $label }}</a></div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">{{ $type === 'leave' ? 'Jenis & Periode' : 'Tanggal & Jam' }}</th><th class="px-5 py-3">Alasan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Keputusan</th><th class="px-5 py-3"></th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($items as $i)
            <tr>
                <td class="px-5 py-3">@if($type === 'leave')<x-badge :status="$i->type" /> <span class="ml-1">{{ $i->start_date->format('d/m/Y') }} – {{ $i->end_date->format('d/m/Y') }}</span>@else{{ $i->work_date->format('d/m/Y') }} · {{ substr($i->start_time, 0, 5) }}–{{ substr($i->end_time, 0, 5) }}@endif</td>
                <td class="px-5 py-3 text-gray-600 max-w-xs">{{ $i->reason }}</td>
                <td class="px-5 py-3"><x-badge :status="$i->status" /></td>
                <td class="px-5 py-3 text-xs text-gray-600">@if($i->processed_at){{ $i->processor?->name }} · {{ $i->processed_at->format('d/m/Y') }}<br>{{ $i->decision_note }}@else - @endif</td>
                <td class="px-5 py-3 text-right">@if($i->status === 'menunggu')<form method="POST" action="{{ route('pegawai.requests.cancel', [$type, $i->id]) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">@csrf @method('PATCH')<button class="text-red-600 hover:underline">Batalkan</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty>Belum ada pengajuan.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
