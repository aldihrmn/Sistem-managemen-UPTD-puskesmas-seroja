@php $a = $announcement ?? null; @endphp
@csrf
<div class="grid gap-4">
    <x-field name="title" label="Judul" :value="$a?->title" required />
    <div>
        <label for="body" class="block text-sm font-medium mb-1">Isi Pengumuman <span class="text-red-500">*</span></label>
        <textarea id="body" name="body" rows="6" class="w-full rounded-lg border px-3 py-2 text-sm {{ $errors->has('body') ? 'border-red-400' : 'border-gray-300' }}">{{ old('body', $a?->body) }}</textarea>
        @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <x-select name="department_id" label="Tujuan" :options="$departments" :value="$a?->department_id" placeholder="Semua unit kerja" />
    <x-field name="published_at" type="datetime-local" label="Waktu terbit" :value="$a?->published_at?->format('Y-m-d\TH:i')" hint="Kosongkan untuk terbit sekarang." />
</div>
<div class="mt-6 flex gap-2">
    <button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button>
    <a href="{{ route('admin.announcements.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm hover:bg-gray-50">Batal</a>
</div>
