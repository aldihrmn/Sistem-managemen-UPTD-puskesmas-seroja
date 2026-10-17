@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <p class="text-sm text-gray-500">{{ $employees->total() }} pegawai ditemukan</p>
    <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"><x-icon name="plus" class="w-4 h-4" /> Tambah Pegawai</a>
</div>

<form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
    <div class="lg:col-span-2">
        <label class="block text-xs font-medium text-gray-500 mb-1">Cari nama / NIP / email</label>
        <input name="q" value="{{ request('q') }}" placeholder="Cari…" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 outline-none">
    </div>
    @foreach([['department_id', 'Unit Kerja', $departments], ['position_id', 'Jabatan', $positions]] as [$n, $l, $opts])
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $l }}</label>
            <select name="{{ $n }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
                <option value="">Semua</option>
                @foreach($opts as $id => $name)<option value="{{ $id }}" @selected(request($n) == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
    @endforeach
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Profesi</label>
        <select name="profession" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
            <option value="">Semua</option>
            @foreach($professions as $p)<option value="{{ $p }}" @selected(request('profession') === $p)>{{ $p }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">
            <option value="">Semua</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
    </div>
    <div class="sm:col-span-2 lg:col-span-6 flex gap-2">
        <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900">Terapkan</button>
        <a href="{{ route('admin.employees.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Reset</a>
    </div>
</form>

<div class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
            <tr><th class="px-5 py-3">Nama</th><th class="px-5 py-3">NIP</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Jabatan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($employees as $e)
            <tr>
                <td class="px-5 py-3"><div class="font-medium">{{ $e->full_name }}</div><div class="text-xs text-gray-500">{{ $e->profession }}</div></td>
                <td class="px-5 py-3 text-gray-600">{{ $e->nip ?? '-' }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $e->department?->name ?? '-' }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $e->position?->name ?? '-' }}</td>
                <td class="px-5 py-3"><x-badge :status="$e->is_active ? 'aktif' : 'nonaktif'" /></td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('admin.employees.show', $e) }}" class="text-emerald-700 hover:underline">Detail</a>
                    <a href="{{ route('admin.employees.edit', $e) }}" class="ml-3 text-gray-600 hover:underline">Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty>Belum ada data pegawai yang sesuai.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $employees->links() }}</div>
@endsection
