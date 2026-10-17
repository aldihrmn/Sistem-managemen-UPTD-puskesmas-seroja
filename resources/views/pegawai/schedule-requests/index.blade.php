@extends('layouts.app')
@section('title', 'Pengajuan Jadwal')
@section('content')
<div class="mb-4 flex justify-end"><a href="{{ route('pegawai.schedule-requests.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"><x-icon name="plus" class="w-4 h-4" /> Buat Pengajuan</a></div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">Alasan</th><th class="px-5 py-3">Diajukan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Keputusan</th><th class="px-5 py-3"></th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($requests as $r)
            <tr>
                <td class="px-5 py-3">{{ \App\Models\ScheduleRequest::TYPES[$r->type] }}@if($r->targetEmployee)<div class="text-xs text-gray-500">dengan {{ $r->targetEmployee->full_name }}</div>@endif</td>
                <td class="px-5 py-3 text-gray-600 max-w-xs">{{ $r->reason }}</td>
                <td class="px-5 py-3 whitespace-nowrap">{{ $r->created_at->translatedFormat('d M Y H:i') }}</td>
                <td class="px-5 py-3"><x-badge :status="$r->status" /></td>
                <td class="px-5 py-3 text-xs text-gray-600">@if($r->processed_at){{ $r->processor?->name }} · {{ $r->processed_at->format('d/m/Y') }}<br>{{ $r->decision_note }}@else - @endif</td>
                <td class="px-5 py-3 text-right">@if($r->status === 'menunggu')<form method="POST" action="{{ route('pegawai.schedule-requests.cancel', $r) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">@csrf @method('PATCH')<button class="text-red-600 hover:underline">Batalkan</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty>Belum ada pengajuan.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $requests->links() }}</div>
@endsection
