@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('content')
<div class="max-w-3xl space-y-6">
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-center gap-4">
                @if($employee->photo)
                    <img src="{{ asset('storage/'.$employee->photo) }}" alt="Foto {{ $employee->full_name }}" class="h-16 w-16 rounded-full object-cover">
                @else
                    <span class="h-16 w-16 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xl font-semibold">{{ strtoupper(mb_substr($employee->full_name, 0, 1)) }}</span>
                @endif
                <div>
                    <h2 class="text-xl font-semibold">{{ $employee->full_name }}</h2>
                    <div class="mt-1"><x-badge :status="$employee->is_active ? 'aktif' : 'nonaktif'" /></div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.employees.edit', $employee) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50">Edit</a>
                <form method="POST" action="{{ route('admin.employees.toggle', $employee) }}" onsubmit="return confirm('{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }} pegawai ini?')">
                    @csrf @method('PATCH')
                    <button class="rounded-lg px-4 py-2 text-sm text-white {{ $employee->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">{{ $employee->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
            </div>
        </div>

        <dl class="mt-6 grid gap-x-6 gap-y-4 sm:grid-cols-2 text-sm">
            @foreach([
                'NIP' => $employee->nip, 'Jenis Kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][$employee->gender] ?? null,
                'Tempat, Tanggal Lahir' => trim(($employee->birth_place ?? '').($employee->birth_date ? ', '.$employee->birth_date->translatedFormat('d F Y') : ''), ', '),
                'Telepon' => $employee->phone, 'Email' => $employee->email, 'Alamat' => $employee->address,
                'Pendidikan' => $employee->education, 'Profesi' => $employee->profession,
                'Unit Kerja' => $employee->department?->name, 'Jabatan' => $employee->position?->name,
                'Status Kepegawaian' => $employee->employment_status, 'Mulai Bekerja' => $employee->start_date?->translatedFormat('d F Y'),
            ] as $label => $val)
                <div><dt class="text-gray-500">{{ $label }}</dt><dd class="font-medium">{{ $val ?: '-' }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold mb-3">Akun Login</h3>
        @if($employee->user)
            <p class="text-sm text-gray-600 mb-4">Email akun: <strong>{{ $employee->user->email }}</strong> · Role: {{ $employee->user->role }}</p>
            <form method="POST" action="{{ route('admin.employees.reset-password', $employee) }}" class="grid gap-3 sm:grid-cols-2 max-w-xl">
                @csrf
                <x-field name="password" type="password" label="Password Baru" autocomplete="new-password" />
                <x-field name="password_confirmation" type="password" label="Konfirmasi Password" autocomplete="new-password" />
                <div class="sm:col-span-2"><button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900">Reset Password</button></div>
            </form>
        @else
            <p class="text-sm text-gray-600">Pegawai ini belum memiliki akun login. Buat melalui halaman <a class="text-emerald-700 hover:underline" href="{{ route('admin.employees.edit', $employee) }}">Edit</a>.</p>
        @endif
    </section>
</div>
@endsection
