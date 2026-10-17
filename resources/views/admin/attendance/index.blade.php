@extends('layouts.app')
@section('title', 'Absensi')
@section('content')
<form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
    <div><label class="block text-xs text-gray-500 mb-1">Dari</label><input type="date" name="from" value="{{ $from }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-gray-500 mb-1">Sampai</label><input type="date" name="to" value="{{ $to }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-gray-500 mb-1">Unit Kerja</label><select name="department_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($departments as $id => $n)<option value="{{ $id }}" @selected(request('department_id') == $id)>{{ $n }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Pegawai</label><select name="employee_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($employees as $id => $n)<option value="{{ $id }}" @selected(request('employee_id') == $id)>{{ $n }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Status</label><select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($statuses as $st)<option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>@endforeach</select></div>
    <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Terapkan</button>
</form>

<div class="mt-4 flex flex-wrap gap-4 text-sm">
    @foreach($statuses as $st)<div class="flex items-center gap-2"><x-badge :status="$st" /><span class="font-medium">{{ $summary[$st] ?? 0 }}</span></div>@endforeach
</div>

<details class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm">
    <summary class="cursor-pointer px-5 py-3 font-medium text-sm">Catat absensi manual</summary>
    <form method="POST" action="{{ route('admin.attendance.store') }}" class="p-5 pt-2 grid gap-3 sm:grid-cols-3 items-start">
        @csrf
        <x-select name="employee_id" label="Pegawai" :options="$employees" />
        <x-field name="work_date" type="date" label="Tanggal" :value="$from" />
        <x-select name="status" label="Status" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])" placeholder="—" />
        <x-field name="check_in" type="time" label="Jam Masuk" />
        <x-field name="check_out" type="time" label="Jam Pulang" />
        <x-field name="reason" label="Alasan pencatatan" required />
        <div class="sm:col-span-3"><button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button></div>
    </form>
</details>

<div class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Pegawai</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Masuk</th><th class="px-5 py-3">Pulang</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Koreksi</th></tr></thead>
        <tbody class="divide-y divide-gray-50">
        @forelse($records as $r)
            <tr>
                <td class="px-5 py-3"><div class="font-medium">{{ $r->employee->full_name }}</div><div class="text-xs text-gray-500">{{ $r->employee->department?->name }}</div></td>
                <td class="px-5 py-3">{{ $r->work_date->format('d/m/Y') }}</td>
                <td class="px-5 py-3">{{ $r->check_in ? substr($r->check_in, 0, 5) : '-' }}</td>
                <td class="px-5 py-3">{{ $r->check_out ? substr($r->check_out, 0, 5) : '-' }}</td>
                <td class="px-5 py-3"><x-badge :status="$r->status" /></td>
                <td class="px-5 py-3">
                    @if($r->corrector)<div class="text-xs text-gray-500 mb-1">Oleh {{ $r->corrector->name }}: {{ $r->correction_reason }}</div>@endif
                    <details><summary class="cursor-pointer text-emerald-700">Koreksi</summary>
                        <form method="POST" action="{{ route('admin.attendance.update', $r) }}" class="mt-2 grid gap-2 w-64">
                            @csrf @method('PUT')
                            <select name="status" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm bg-white">@foreach($statuses as $st)<option value="{{ $st }}" @selected($r->status === $st)>{{ ucfirst($st) }}</option>@endforeach</select>
                            <input type="time" name="check_in" value="{{ $r->check_in ? substr($r->check_in, 0, 5) : '' }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                            <input type="time" name="check_out" value="{{ $r->check_out ? substr($r->check_out, 0, 5) : '' }}" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                            <input name="reason" placeholder="Alasan koreksi (wajib)" required class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                            <button class="rounded-lg bg-gray-800 px-3 py-1.5 text-sm text-white">Simpan koreksi</button>
                        </form>
                    </details>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><x-empty>Belum ada data absensi pada periode ini.</x-empty></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $records->links() }}</div>
@if($errors->any())<p class="mt-2 text-sm text-red-600">{{ $errors->first() }}</p>@endif
@endsection
