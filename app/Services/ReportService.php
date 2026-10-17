<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\OvertimeRequest;
use App\Models\ScheduleRequest;
use App\Models\Setting;
use Carbon\Carbon;

class ReportService
{
    public static function columns(): array
    {
        return [
            'Nama' => 'name', 'NIP' => 'nip', 'Unit Kerja' => 'department', 'Hari Kerja' => 'workdays', 'Hadir' => 'present',
            'Terlambat' => 'late', 'Izin' => 'izin', 'Sakit' => 'sakit', 'Cuti' => 'cuti', 'Tidak Hadir' => 'absent',
            'Jadwal' => 'scheduled', 'Dibatalkan' => 'cancelled', 'Lembur (jam)' => 'overtime_hours',
            'Pengajuan' => 'req_total', 'Disetujui' => 'req_approved', 'Ditolak' => 'req_rejected', 'Menunggu' => 'req_pending',
        ];
    }

    private const SUM_KEYS = ['workdays', 'present', 'late', 'izin', 'sakit', 'cuti', 'absent', 'scheduled', 'cancelled',
        'overtime_hours', 'req_total', 'req_approved', 'req_rejected', 'req_pending'];

    public static function monthly(int $month, int $year, array $f = [], ?int $onlyEmployee = null): array
    {
        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth();
        $fromS = $from->toDateString();
        $toS = $to->toDateString();

        $employees = Employee::with(['department', 'position'])
            ->when($onlyEmployee, fn ($q) => $q->where('id', $onlyEmployee))
            ->when(! empty($f['q']), fn ($q) => $q->where(fn ($w) => $w->where('full_name', 'like', "%{$f['q']}%")->orWhere('nip', 'like', "%{$f['q']}%")))
            ->when(! empty($f['department_id']), fn ($q) => $q->where('department_id', $f['department_id']))
            ->when(! empty($f['position_id']), fn ($q) => $q->where('position_id', $f['position_id']))
            ->when(! empty($f['profession']), fn ($q) => $q->where('profession', $f['profession']))
            ->when(! empty($f['employment_status']), fn ($q) => $q->where('employment_status', $f['employment_status']))
            ->orderBy('full_name')->get();

        $ids = $employees->pluck('id');
        $holidays = Holiday::whereBetween('date', [$fromS, $toS])->get()->map(fn ($h) => $h->date->toDateString())->all();

        $sched = EmployeeSchedule::whereIn('employee_id', $ids)->whereBetween('work_date', [$fromS, $toS])->get()->groupBy('employee_id');
        $att = AttendanceRecord::whereIn('employee_id', $ids)->whereBetween('work_date', [$fromS, $toS])->get()->groupBy('employee_id');
        $ot = OvertimeRequest::whereIn('employee_id', $ids)->where('status', 'disetujui')->whereBetween('work_date', [$fromS, $toS])->get()->groupBy('employee_id');
        $req = ScheduleRequest::whereIn('employee_id', $ids)->whereBetween('created_at', [$from, $to->copy()->endOfDay()])->get()->groupBy('employee_id');

        $rows = collect();
        foreach ($employees as $e) {
            $s = $sched->get($e->id, collect());
            $a = $att->get($e->id, collect());
            $o = $ot->get($e->id, collect());
            $rq = $req->get($e->id, collect());

            $active = $s->where('status', 'aktif')->filter(fn ($x) => ! in_array($x->work_date->toDateString(), $holidays));
            $attBySched = $a->whereNotNull('schedule_id')->keyBy('schedule_id');
            $missing = $active->filter(fn ($x) => $x->work_date->lt(today()))->reject(fn ($x) => $attBySched->has($x->id))->count();

            $minutes = $o->sum(fn ($x) => abs(Carbon::parse($x->start_time)->diffInMinutes(Carbon::parse($x->end_time))));

            $row = [
                'id' => $e->id, 'name' => $e->full_name, 'nip' => $e->nip ?: '-', 'department' => $e->department?->name ?? '-',
                'position' => $e->position?->name ?? '-', 'profession' => $e->profession ?? '-', 'active' => (bool) $e->is_active,
                'workdays' => $active->pluck('work_date')->map(fn ($d) => $d->toDateString())->unique()->count(),
                'scheduled' => $active->count(),
                'cancelled' => $s->where('status', 'dibatalkan')->count(),
                'present' => $a->whereIn('status', ['hadir', 'terlambat'])->pluck('work_date')->map(fn ($d) => $d->toDateString())->unique()->count(),
                'late' => $a->where('status', 'terlambat')->count(),
                'izin' => $a->where('status', 'izin')->count(),
                'sakit' => $a->where('status', 'sakit')->count(),
                'cuti' => $a->where('status', 'cuti')->count(),
                'absent' => $a->where('status', 'alpa')->count() + $missing,
                'overtime_hours' => round($minutes / 60, 1),
                'req_total' => $rq->count(), 'req_approved' => $rq->where('status', 'disetujui')->count(),
                'req_rejected' => $rq->where('status', 'ditolak')->count(), 'req_pending' => $rq->where('status', 'menunggu')->count(),
            ];

            $hasData = $row['scheduled'] + $row['cancelled'] + $a->count() > 0;
            if ($onlyEmployee || $row['active'] || $hasData) {
                $rows->push($row);
            }
        }

        $totals = [];
        foreach (self::SUM_KEYS as $k) {
            $totals[$k] = $rows->sum($k);
        }

        $byDept = $rows->groupBy('department')->map(function ($g, $name) {
            $r = ['department' => $name, 'employees' => $g->count()];
            foreach (self::SUM_KEYS as $k) {
                $r[$k] = $g->sum($k);
            }
            return $r;
        })->values();

        return [
            'from' => $from, 'to' => $to, 'rows' => $rows, 'byDept' => $byDept, 'totals' => $totals,
            'activeCount' => $rows->where('active', true)->count(), 'holidays' => $holidays,
        ];
    }

    public static function meta(array $report, $user): array
    {
        return [
            'report' => $report,
            'institution' => Setting::get('institution_name', 'Puskesmas Seroja'),
            'creator' => $user->name,
            'generatedAt' => now(),
            'periodLabel' => $report['from']->translatedFormat('F Y'),
        ];
    }
}
