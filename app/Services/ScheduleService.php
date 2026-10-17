<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    /** Batas waktu absolut (mendukung jadwal lintas tengah malam). */
    public static function bounds(string $date, string $start, string $end, bool $crosses): array
    {
        $s = Carbon::parse($date.' '.$start);
        $e = Carbon::parse($date.' '.$end);
        if ($crosses) {
            $e->addDay();
        }
        return [$s, $e];
    }

    public static function conflicts(int $employeeId, string $date, string $start, string $end, bool $crosses, array $ignoreIds = []): Collection
    {
        [$s, $e] = self::bounds($date, $start, $end, $crosses);
        $d = Carbon::parse($date);

        return EmployeeSchedule::where('employee_id', $employeeId)->where('status', 'aktif')
            ->whereBetween('work_date', [$d->copy()->subDay()->toDateString(), $d->copy()->addDay()->toDateString()])
            ->when($ignoreIds, fn ($q) => $q->whereNotIn('id', $ignoreIds))
            ->get()
            ->filter(function ($x) use ($s, $e) {
                [$xs, $xe] = self::bounds($x->work_date->toDateString(), $x->start_time, $x->end_time, (bool) $x->crosses_midnight);
                return $xs->lt($e) && $s->lt($xe);
            })->values();
    }

    public static function approvedLeave(int $employeeId, string $date): ?LeaveRequest
    {
        return LeaveRequest::where('employee_id', $employeeId)->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
    }

    /** Daftar masalah (kosong = aman disimpan). */
    public static function problems(int $employeeId, string $date, string $start, string $end, bool $crosses, array $ignoreIds = []): array
    {
        $emp = Employee::find($employeeId);
        if (! $emp || ! $emp->is_active) {
            return ['Pegawai tidak ditemukan atau tidak aktif.'];
        }

        $msgs = [];
        foreach (self::conflicts($employeeId, $date, $start, $end, $crosses, $ignoreIds) as $c) {
            $msgs[] = "{$emp->full_name}: bentrok dengan jadwal ".$c->work_date->format('d/m/Y').' '.substr($c->start_time, 0, 5).'–'.substr($c->end_time, 0, 5).($c->location ? " ({$c->location})" : '').'.';
        }
        if ($l = self::approvedLeave($employeeId, $date)) {
            $msgs[] = "{$emp->full_name}: berstatus {$l->type} (disetujui) pada ".Carbon::parse($date)->format('d/m/Y').'.';
        }
        return $msgs;
    }

    public static function range(string $view, Carbon $date): array
    {
        return match ($view) {
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
            default => [$date->copy()->startOfDay(), $date->copy()->startOfDay()],
        };
    }

    public static function step(string $view, Carbon $date, int $dir): Carbon
    {
        return match ($view) {
            'week' => $date->copy()->addWeeks($dir),
            'month' => $dir > 0 ? $date->copy()->addMonthNoOverflow() : $date->copy()->subMonthNoOverflow(),
            default => $date->copy()->addDays($dir),
        };
    }

    public static function snapshot(EmployeeSchedule $s): array
    {
        return [
            'id' => $s->id, 'employee_id' => $s->employee_id, 'work_date' => $s->work_date->toDateString(),
            'start_time' => substr($s->start_time, 0, 5), 'end_time' => substr($s->end_time, 0, 5), 'status' => $s->status,
        ];
    }
}
