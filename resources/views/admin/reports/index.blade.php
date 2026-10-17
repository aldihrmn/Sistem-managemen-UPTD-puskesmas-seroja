@extends('layouts.app')
@section('title', 'Laporan Bulanan')
@section('content')
@php $qs = request()->query(); @endphp
<form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
    <div><label class="block text-xs text-gray-500 mb-1">Bulan</label><select name="month" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected((int) request('month', now()->month) === $m)>{{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Tahun</label><input type="number" name="year" value="{{ request('year', now()->year) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-gray-500 mb-1">Nama / NIP</label><input name="q" value="{{ request('q') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-gray-500 mb-1">Unit Kerja</label><select name="department_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($departments as $id => $n)<option value="{{ $id }}" @selected(request('department_id') == $id)>{{ $n }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Profesi</label><select name="profession" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($professions as $p)<option value="{{ $p }}" @selected(request('profession') === $p)>{{ $p }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Jabatan</label><select name="position_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($positions as $id => $n)<option value="{{ $id }}" @selected(request('position_id') == $id)>{{ $n }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Status Kepegawaian</label><select name="employment_status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"><option value="">Semua</option>@foreach($statuses as $st)<option value="{{ $st }}" @selected(request('employment_status') === $st)>{{ $st }}</option>@endforeach</select></div>
    <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Tampilkan</button>
</form>

<div class="mt-4 flex flex-wrap gap-2">
    <a target="_blank" href="{{ route('admin.reports.print', $qs) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">Cetak</a>
    <a href="{{ route('admin.reports.pdf', $qs) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Unduh PDF</a>
    <a href="{{ route('admin.reports.excel', $qs) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Ekspor Excel</a>
</div>

<div class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 overflow-x-auto">@include('reports._content')</div>
@endsection
