<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use Carbon\CarbonPeriod;

class LeaveService
{
    /** Cuti/izin/sakit yang disetujui -> catatan absensi pada tiap jadwal aktif (tanpa duplikasi). */
    public static function applyApproved(LeaveRequest $l): void
    {
        foreach (CarbonPeriod::create($l->start_date, $l->end_date) as $day) {
            $schedules = EmployeeSchedule::where('employee_id', $l->employee_id)->where('status', 'aktif')
                ->whereDate('work_date', $day->toDateString())->get();

            foreach ($schedules as $s) {
                $rec = AttendanceRecord::firstOrNew(['employee_id' => $l->employee_id, 'schedule_id' => $s->id]);
                if ($rec->exists && $rec->check_in) {
                    continue; // sudah benar-benar hadir, jangan ditimpa
                }
                $rec->fill(['work_date' => $day->toDateString(), 'status' => $l->type, 'check_in' => null, 'check_out' => null,
                    'notes' => "Pengajuan #{$l->id}"])->save();
            }
        }
    }
}
