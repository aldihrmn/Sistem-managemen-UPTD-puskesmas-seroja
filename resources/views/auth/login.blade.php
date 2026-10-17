<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Puskesmas Seroja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">
<div class="min-h-screen grid lg:grid-cols-2">
    <div class="hidden lg:flex flex-col justify-between bg-emerald-700 p-12 text-white">
        <div class="text-lg font-semibold tracking-wide">Puskesmas Seroja</div>
        <div>
            <h1 class="text-3xl font-semibold leading-snug">Sistem Informasi<br>Manajemen Pegawai</h1>
            <p class="mt-3 text-emerald-100 max-w-sm">Jadwal kerja, absensi, pengajuan, dan laporan pegawai dalam satu tempat.</p>
        </div>
        <div class="text-sm text-emerald-200">&copy; {{ date('Y') }} Puskesmas Seroja</div>
    </div>

    <div class="flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <h2 class="text-2xl font-semibold">Masuk</h2>
            <p class="text-sm text-gray-500 mt-1">Gunakan akun yang diberikan oleh admin.</p>

            @if (session('status'))
                <div class="mt-4 rounded-lg bg-emerald-50 text-emerald-700 text-sm px-4 py-3">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 outline-none @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-24 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 outline-none @error('password') border-red-400 @enderror">
                        <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 px-3 text-sm text-gray-500 hover:text-emerald-700">Tampilkan</button>
                    </div>
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600"> Ingat saya
                </label>

                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-2.5 font-medium text-white hover:bg-emerald-700 transition">Masuk</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    });
</script>
</body>
</html>
