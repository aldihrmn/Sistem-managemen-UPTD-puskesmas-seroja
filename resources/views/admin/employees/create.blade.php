@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('content')
<form method="POST" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-3xl">
    @include('admin.employees._form')
</form>
@endsection
