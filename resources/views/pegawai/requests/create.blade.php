@extends('layouts.app')
@section('title', 'Ajukan '.$label)
@section('content')
<form method="POST" action="{{ route('pegawai.requests.store', $type) }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-2xl">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        @if($type === 'leave')
            <x-select name="type" label="Jenis" :options="['cuti' => 'Cuti', 'izin' => 'Izin', 'sakit' => 'Sakit']" placeholder="— Pilih —" class="sm:col-span-2" />
            <x-field name="start_date" type="date" label="Tanggal mulai" required />
            <x-field name="end_date" type="date" label="Tanggal selesai" required />
        @else
            <x-field name="work_date" type="date" label="Tanggal lembur" required class="sm:col-span-2" />
            <x-field name="start_time" type="time" label="Jam mulai" required />
            <x-field name="end_time" type="time" label="Jam selesai" required />
        @endif
        <div class="sm:col-span-2">
            <label for="reason" class="block text-sm font-medium mb-1">Alasan <span class="text-red-500">*</span></label>
            <textarea id="reason" name="reason" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm {{ $errors->has('reason') ? 'border-red-400' : 'border-gray-300' }}">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @if($type === 'leave')
            <div class="sm:col-span-2"><label for="attachment" class="block text-sm font-medium mb-1">Lampiran (mis. surat dokter, opsional)</label><input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" class="text-sm">@error('attachment')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        @endif
    </div>
    <div class="mt-6 flex gap-2">
        <button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">Kirim Pengajuan</button>
        <a href="{{ route('pegawai.requests.index', $type) }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm hover:bg-gray-50">Batal</a>
    </div>
</form>
@endsection
