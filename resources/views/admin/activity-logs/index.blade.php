@extends('layouts.app')
@section('title', 'Riwayat Aktivitas')
@section('content')
<form method="GET" class="mb-4 flex gap-2 max-w-md"><input name="q" value="{{ request('q') }}" placeholder="Cari aktivitas…" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm"><button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Cari</button></form>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Pengguna</th><th class="px-5 py-3">Aktivitas</th><th class="px-5 py-3">Detail</th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($logs as $l)
            <tr>
                <td class="px-5 py-3 whitespace-nowrap text-gray-600">{{ $l->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-5 py-3">{{ $l->user?->name ?? 'Sistem' }}</td>
                <td class="px-5 py-3"><div>{{ $l->description }}</div><div class="text-xs text-gray-400">{{ $l->action }}</div></td>
                <td class="px-5 py-3">@if($l->properties)<details><summary class="cursor-pointer text-emerald-700 text-xs">Lihat</summary><pre class="mt-1 rounded bg-gray-50 p-2 text-xs max-w-xs overflow-x-auto">{{ json_encode($l->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif</td>
            </tr>
        @empty
            <tr><td colspan="4"><x-empty>Belum ada aktivitas tercatat.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
