<style>
.rpt{font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#111}
.rpt h1{font-size:16px;margin:0 0 2px}.rpt h3{font-size:13px;margin:16px 0 4px}
.rpt table{width:100%;border-collapse:collapse;margin-top:6px}
.rpt th,.rpt td{border:1px solid #bbb;padding:3px 5px;text-align:left}
.rpt th{background:#e8f5ee}.rpt td.n{text-align:right}.rpt tr.t td{font-weight:bold;background:#f4f4f4}
.rpt .muted{color:#555}
</style>
@php $cols = \App\Services\ReportService::columns(); $rows = $report['rows']; @endphp
<div class="rpt">
    <h1>{{ $institution }}</h1>
    <div><strong>Laporan Bulanan Kehadiran Pegawai</strong></div>
    <div>Periode: {{ $periodLabel }}</div>
    <div class="muted">Dibuat: {{ $generatedAt->format('d/m/Y H:i') }} oleh {{ $creator }}</div>
    <p>Jumlah pegawai aktif dalam laporan: <strong>{{ $report['activeCount'] }}</strong></p>

    <table>
        <thead><tr><th>No</th>@foreach($cols as $label => $key)<th>{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($rows as $i => $row)
            <tr><td class="n">{{ $i + 1 }}</td>@foreach($cols as $key)<td class="{{ is_numeric($row[$key]) && $key !== 'nip' ? 'n' : '' }}">{{ $row[$key] }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($cols) + 1 }}" style="text-align:center">Tidak ada data pada periode ini.</td></tr>
        @endforelse
        @if($rows->count())
            <tr class="t"><td colspan="4">Total</td>@foreach(array_slice(array_values($cols), 3) as $key)<td class="n">{{ $report['totals'][$key] ?? '' }}</td>@endforeach</tr>
        @endif
        </tbody>
    </table>

    <h3>Ringkasan per Unit Kerja</h3>
    <table>
        <thead><tr><th>Unit Kerja</th><th>Pegawai</th><th>Hari Kerja</th><th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Cuti</th><th>Tidak Hadir</th><th>Lembur (jam)</th></tr></thead>
        <tbody>
        @forelse($report['byDept'] as $d)
            <tr><td>{{ $d['department'] }}</td>@foreach(['employees', 'workdays', 'present', 'late', 'izin', 'sakit', 'cuti', 'absent', 'overtime_hours'] as $k)<td class="n">{{ $d[$k] }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="10" style="text-align:center">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p class="muted" style="margin-top:8px">Hari kerja dihitung dari jadwal aktif (tidak termasuk hari libur terdaftar). "Tidak Hadir" mencakup status alpa dan jadwal yang telah lewat tanpa catatan absensi. Izin/sakit/cuti berasal dari pengajuan yang disetujui atau pencatatan admin.</p>
</div>
