@extends('layouts.app')
@section('title', 'Tambah Jadwal')
@section('content')
<form method="POST" action="{{ route('admin.schedules.store') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-3xl">
    @include('admin.schedules._form')
</form>
@endsection
