@extends('layouts.app')
@section('title', 'Edit Jadwal')
@section('content')
<form method="POST" action="{{ route('admin.schedules.update', $schedule) }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-3xl">
    @method('PUT')
    @include('admin.schedules._form')
</form>
@endsection
