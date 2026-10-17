@extends('layouts.app')
@section('title', 'Buat Pengumuman')
@section('content')
<form method="POST" action="{{ route('admin.announcements.store') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-2xl">@include('admin.announcements._form')</form>
@endsection
