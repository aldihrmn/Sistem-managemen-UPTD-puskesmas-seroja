@extends('layouts.app')
@section('title', 'Pengumuman')
@section('content')
<div class="mb-4 flex justify-end"><a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"><x-icon name="plus" class="w-4 h-4" /> Buat Pengumuman</a></div>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y divide-gray-50">
    @forelse($announcements as $a)
        <div class="p-5 flex items-start justify-between gap-4">
            <div><div class="font-medium">{{ $a->title }}</div><div class="text-xs text-gray-500">{{ $a->department?->name ?? 'Semua unit' }} · {{ $a->published_at?->translatedFormat('d M Y H:i') ?? 'Draf' }}</div>
                <div class="mt-1 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($a->body, 140) }}</div></div>
            <div class="flex gap-3 text-sm whitespace-nowrap">
                <a href="{{ route('admin.announcements.edit', $a) }}" class="text-emerald-700 hover:underline">Edit</a>
                <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" onsubmit="return confirm('Hapus pengumuman ini?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Hapus</button></form>
            </div>
        </div>
    @empty
        <x-empty>Belum ada pengumuman.</x-empty>
    @endforelse
</div>
<div class="mt-4">{{ $announcements->links() }}</div>
@endsection
