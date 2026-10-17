@php
    $user = auth()->user();
    $badge = fn ($m, $s = 'menunggu') => $user->isAdmin() ? $m::where('status', $s)->count() : 0;
    $menu = $user->isAdmin() ? [
        ['Dashboard', route('admin.dashboard'), 'home', 'admin/dashboard'],
        ['Data Pegawai', route('admin.employees.index'), 'users', 'admin/employees*'],
        ['Unit Kerja', route('admin.master.index', 'department'), 'building', 'admin/master/department*'],
        ['Jabatan', route('admin.master.index', 'position'), 'briefcase', 'admin/master/position*'],
        ['Jadwal', route('admin.schedules.index'), 'calendar', 'admin/schedules*'],
        ['Jenis Jadwal', route('admin.shifts.index'), 'clock', 'admin/shifts*'],
        ['Pengajuan Jadwal', route('admin.schedule-requests.index'), 'clipboard', 'admin/schedule-requests*', $badge(\App\Models\ScheduleRequest::class)],
        ['Absensi', route('admin.attendance.index'), 'clipboard', 'admin/attendance*'],
        ['Cuti / Izin / Sakit', route('admin.approvals.index', 'leave'), 'clipboard', 'admin/approvals/leave*', $badge(\App\Models\LeaveRequest::class)],
        ['Lembur', route('admin.approvals.index', 'overtime'), 'clock', 'admin/approvals/overtime*', $badge(\App\Models\OvertimeRequest::class)],
        ['Laporan Bulanan', route('admin.reports.index'), 'chart', 'admin/reports*'],
        ['Pengumuman', route('admin.announcements.index'), 'megaphone', 'admin/announcements*'],
        ['Riwayat Aktivitas', route('admin.activity-logs.index'), 'clipboard', 'admin/activity-logs*'],
        ['Pengaturan', route('admin.settings.index'), 'sliders', 'admin/settings*'],
    ] : [
        ['Dashboard', route('pegawai.dashboard'), 'home', 'pegawai/dashboard'],
        ['Jadwal', route('pegawai.schedules.index'), 'calendar', 'pegawai/schedules*'],
        ['Pengajuan Jadwal', route('pegawai.schedule-requests.index'), 'clipboard', 'pegawai/schedule-requests*'],
        ['Absensi', route('pegawai.attendance.index'), 'clock', 'pegawai/attendance*'],
        ['Cuti / Izin / Sakit', route('pegawai.requests.index', 'leave'), 'clipboard', 'pegawai/requests/leave*'],
        ['Lembur', route('pegawai.requests.index', 'overtime'), 'clock', 'pegawai/requests/overtime*'],
        ['Laporan Saya', route('pegawai.report.index'), 'chart', 'pegawai/report*'],
        ['Pengumuman', route('pegawai.announcements.index'), 'megaphone', 'pegawai/announcements*'],
        ['Profil', route('pegawai.profile.edit'), 'users', 'pegawai/profile*'],
    ];
    $unread = $user->unreadNotifications()->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Puskesmas Seroja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
<div id="overlay" class="fixed inset-0 z-30 bg-black/40 hidden lg:hidden"></div>
<div class="min-h-screen lg:flex">
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transition-transform lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen shrink-0 bg-white border-r border-gray-100 flex flex-col">
        <div class="h-16 px-6 flex items-center border-b border-gray-100">
            <span class="text-lg font-semibold text-emerald-700">Puskesmas Seroja</span>
        </div>
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            @foreach($menu as $m)
                <a href="{{ $m[1] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->is($m[3]) ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <x-icon :name="$m[2]" /> <span class="flex-1">{{ $m[0] }}</span>
                    @if(($m[4] ?? 0) > 0)<span class="rounded-full bg-amber-100 px-2 text-xs text-amber-700">{{ $m[4] }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="p-4 border-t border-gray-100 text-xs text-gray-400">{{ $user->isAdmin() ? 'Administrator' : 'Pegawai' }}</div>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="h-16 bg-white border-b border-gray-100 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button id="menuBtn" type="button" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-gray-100" aria-label="Menu"><x-icon name="menu" class="w-6 h-6" /></button>
                <h1 class="text-lg font-semibold">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-500 hover:text-emerald-700" title="Notifikasi">
                    <x-icon name="bell" class="w-6 h-6" />
                    @if($unread > 0)<span class="absolute top-1 right-1 h-4 min-w-4 px-1 rounded-full bg-red-500 text-white text-[10px] flex items-center justify-center">{{ $unread }}</span>@endif
                </a>
                <details class="relative">
                    <summary class="list-none cursor-pointer flex items-center gap-2">
                        <span class="h-9 w-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-semibold">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="hidden sm:block text-sm font-medium">{{ $user->name }}</span>
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl bg-white border border-gray-100 shadow-lg p-2">
                        <div class="px-3 py-2 text-xs text-gray-500 truncate">{{ $user->email }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"><x-icon name="logout" class="w-4 h-4" /> Keluar</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main class="p-4 sm:p-6">
            @if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 text-sm px-4 py-3">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-4 rounded-lg bg-red-50 text-red-800 text-sm px-4 py-3">{{ session('error') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
<script>
    const sb = document.getElementById('sidebar'), ov = document.getElementById('overlay');
    const toggle = () => { sb.classList.toggle('-translate-x-full'); ov.classList.toggle('hidden'); };
    document.getElementById('menuBtn').addEventListener('click', toggle);
    ov.addEventListener('click', toggle);
</script>
</body>
</html>
