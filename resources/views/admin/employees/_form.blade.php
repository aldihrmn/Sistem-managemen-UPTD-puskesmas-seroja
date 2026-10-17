@php $e = $employee ?? null; @endphp
@csrf
@if($errors->any())<div class="mb-4 rounded-lg bg-red-50 text-red-800 text-sm px-4 py-3">Periksa kembali isian Anda.</div>@endif

<div class="grid gap-4 sm:grid-cols-2">
    <x-field name="full_name" label="Nama Lengkap" :value="$e?->full_name" required class="sm:col-span-2" />
    <x-field name="nip" label="NIP / Nomor Identitas" :value="$e?->nip" hint="Boleh dikosongkan." />
    <x-select name="gender" label="Jenis Kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="$e?->gender" />
    <x-field name="birth_place" label="Tempat Lahir" :value="$e?->birth_place" />
    <x-field name="birth_date" type="date" label="Tanggal Lahir" :value="$e?->birth_date?->format('Y-m-d')" />
    <x-field name="phone" label="Nomor Telepon" :value="$e?->phone" />
    <x-field name="email" type="email" label="Email" :value="$e?->email" />
    <x-field name="address" label="Alamat" :value="$e?->address" class="sm:col-span-2" />
    <x-field name="education" label="Pendidikan Terakhir" :value="$e?->education" />
    <x-field name="profession" label="Profesi" :value="$e?->profession" />
    <x-select name="department_id" label="Unit Kerja" :options="$departments" :value="$e?->department_id" />
    <x-select name="position_id" label="Jabatan" :options="$positions" :value="$e?->position_id" />
    <x-field name="employment_status" label="Status Kepegawaian" :value="$e?->employment_status" hint="Contoh: PNS, PPPK, Honorer, Kontrak." />
    <x-field name="start_date" type="date" label="Tanggal Mulai Bekerja" :value="$e?->start_date?->format('Y-m-d')" />
    <div class="sm:col-span-2">
        <label for="photo" class="block text-sm font-medium mb-1">Foto Profil</label>
        <input id="photo" name="photo" type="file" accept="image/*" class="text-sm">
        @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <input type="hidden" name="is_active" value="0">
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-emerald-600" @checked(old('is_active', $e?->is_active ?? true))> Pegawai aktif</label>
    </div>
</div>

<div class="mt-6 border-t border-gray-100 pt-5">
    <h3 class="font-semibold mb-2">Akun Login</h3>
    @if($e?->user)
        <p class="text-sm text-gray-600">Akun terhubung: <strong>{{ $e->user->email }}</strong>. Perubahan email dan status aktif disinkronkan ke akun. Reset password ada di halaman detail.</p>
    @else
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" id="create_account" name="create_account" value="1" class="rounded border-gray-300 text-emerald-600" @checked(old('create_account'))> Buat akun login untuk pegawai ini (memakai email di atas)</label>
        <div id="accountFields" class="mt-3 max-w-sm {{ old('create_account') ? '' : 'hidden' }}">
            <x-field name="password" type="password" label="Password Awal" hint="Minimal 8 karakter." autocomplete="new-password" />
        </div>
        <script>
            document.getElementById('create_account').addEventListener('change', e =>
                document.getElementById('accountFields').classList.toggle('hidden', !e.target.checked));
        </script>
    @endif
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button>
    <a href="{{ route('admin.employees.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm hover:bg-gray-50">Batal</a>
</div>
