@extends('layouts.app')
@section('title', 'Laporan Saya')
@section('content')
<form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
    <div><label class="block text-xs text-gray-500 mb-1">Bulan</label><select name="month" class="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white">@foreach(range(1, 12) as $m)<option value="{{ $m }}" @selected((int) request('month', now()->month) === $m)>{{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}</option>@endforeach</select></div>
    <div><label class="block text-xs text-gray-500 mb-1">Tahun</label><input type="number" name="year" value="{{ request('year', now()->year) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-28"></div>
    <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">Tampilkan</button>
    <a target="_blank" href="{{ route('pegawai.report.print', request()->query()) }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm hover:bg-gray-50">Cetak</a>
</form>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 overflow-x-auto">@include('reports._content')</div>
@endsection
