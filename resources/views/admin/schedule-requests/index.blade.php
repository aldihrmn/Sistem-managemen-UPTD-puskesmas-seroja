@extends('layouts.app')
@section('title', 'Pengajuan Jadwal')
@section('content')
<div class="mb-4 inline-flex rounded-lg border border-gray-200 bg-white p-1 text-sm">
    @foreach([null => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'] as $k => $l)
        <a href="{{ route('admin.schedule-requests.index', $k ? ['status' => $k] : []) }}" class="rounded-md px-3 py-1.5 {{ ($status ?: null) === ($k ?: null) ? 'bg-emerald-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $l }}</a>
    @endforeach
</div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Pegawai</th><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">Diajukan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($requests as $r)
            <tr>
                <td class="px-5 py-3 font-medium">{{ $r->employee->full_name }}</td>
                <td class="px-5 py-3">{{ \App\Models\ScheduleRequest::TYPES[$r->type] }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $r->created_at->translatedFormat('d M Y H:i') }}</td>
                <td class="px-5 py-3"><x-badge :status="$r->status" /></td>
                <td class="px-5 py-3 text-right"><a href="{{ route('admin.schedule-requests.show', $r) }}" class="text-emerald-700 hover:underline">{{ $r->status === 'menunggu' ? 'Periksa' : 'Detail' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="5"><x-empty>Belum ada pengajuan.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $requests->links() }}</div>
@endsection
