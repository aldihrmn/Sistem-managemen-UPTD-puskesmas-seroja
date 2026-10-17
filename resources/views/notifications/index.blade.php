@extends('layouts.app')
@section('title', 'Notifikasi')
@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">{{ $notifications->total() }} notifikasi</p>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="text-sm text-emerald-700 hover:underline">Tandai semua dibaca</button></form>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-50">
        @forelse($notifications as $n)
            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                @csrf
                <button class="w-full text-left px-5 py-4 hover:bg-gray-50 {{ $n->read_at ? '' : 'bg-emerald-50/50' }}">
                    <div class="flex justify-between gap-3"><span class="font-medium text-sm">{{ $n->data['title'] ?? '' }}</span><span class="text-xs text-gray-400 shrink-0">{{ $n->created_at->diffForHumans() }}</span></div>
                    <div class="text-sm text-gray-600 mt-0.5">{{ $n->data['message'] ?? '' }}</div>
                </button>
            </form>
        @empty
            <x-empty>Belum ada notifikasi.</x-empty>
        @endforelse
    </div>
    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
@endsection
