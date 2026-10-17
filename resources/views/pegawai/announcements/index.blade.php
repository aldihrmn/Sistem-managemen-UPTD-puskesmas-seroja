@extends('layouts.app')
@section('title', 'Pengumuman')
@section('content')
<div class="max-w-3xl space-y-4">
    @forelse($announcements as $a)
        <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold">{{ $a->title }}</h2>
            <div class="text-xs text-gray-500 mb-2">{{ $a->published_at->translatedFormat('d F Y H:i') }}</div>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $a->body }}</p>
        </article>
    @empty
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm"><x-empty>Belum ada pengumuman.</x-empty></div>
    @endforelse
    {{ $announcements->links() }}
</div>
@endsection
