@extends('layouts.app')
@section('title', $label)
@section('content')
<div class="max-w-4xl space-y-6">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold mb-3">Tambah {{ $label }}</h2>
        <form method="POST" action="{{ route('admin.master.store', $type) }}" class="grid gap-3 sm:grid-cols-3 items-start">
            @csrf
            <x-field name="name" label="Nama" required />
            @if($hasDescription)<x-field name="description" label="Keterangan" />@endif
            <div class="sm:pt-6"><button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button></div>
        </form>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr><th class="px-5 py-3">Nama</th>@if($hasDescription)<th class="px-5 py-3">Keterangan</th>@endif<th class="px-5 py-3">Pegawai</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            @forelse($items as $item)
                <tr>
                    <td class="px-5 py-3" colspan="{{ $hasDescription ? 2 : 1 }}">
                        <form id="f{{ $item->id }}" method="POST" action="{{ route('admin.master.update', [$type, $item->id]) }}" class="flex gap-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $item->name }}" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">
                            @if($hasDescription)<input name="description" value="{{ $item->description }}" placeholder="Keterangan" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm">@endif
                        </form>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $item->employees_count }}</td>
                    <td class="px-5 py-3"><x-badge :status="$item->is_active ? 'aktif' : 'nonaktif'" /></td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <button form="f{{ $item->id }}" class="text-emerald-700 hover:underline">Simpan</button>
                        <form method="POST" action="{{ route('admin.master.toggle', [$type, $item->id]) }}" class="inline" onsubmit="return confirm('Ubah status {{ $label }} ini?')">
                            @csrf @method('PATCH')
                            <button class="ml-3 text-gray-600 hover:underline">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty>Belum ada data {{ strtolower($label) }}.</x-empty></td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    @if($errors->any())<p class="text-sm text-red-600">{{ $errors->first() }}</p>@endif
</div>
@endsection
