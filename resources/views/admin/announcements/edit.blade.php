@extends('layouts.app')
@section('title', 'Edit Pengumuman')
@section('content')
<form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-2xl">@method('PUT')@include('admin.announcements._form')</form>
@endsection
