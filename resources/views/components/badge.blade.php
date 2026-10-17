@props(['status'])
@php
$map = ['hadir'=>'green','aktif'=>'green','disetujui'=>'green','terlambat'=>'amber','menunggu'=>'amber',
        'izin'=>'sky','sakit'=>'sky','cuti'=>'sky','alpa'=>'red','ditolak'=>'red','dibatalkan'=>'gray','nonaktif'=>'gray'];
$tones = [
 'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
 'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
 'sky'   => 'bg-sky-50 text-sky-700 ring-sky-600/20',
 'red'   => 'bg-red-50 text-red-700 ring-red-600/20',
 'gray'  => 'bg-gray-100 text-gray-600 ring-gray-500/20',
];
$tone = $tones[$map[strtolower($status)] ?? 'gray'];
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $tone }}">{{ ucfirst($status) }}</span>
