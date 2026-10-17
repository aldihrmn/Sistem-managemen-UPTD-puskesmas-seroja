<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\ScheduleRequest;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $employee = auth()->user()->employee?->load(['department', 'position']);

        if (! $employee) {
            return view('pegawai.dashboard', ['employee' => null]);
        }

        $today = today();
        $schedules = fn () => EmployeeSchedule::with('shift')->where('employee_id', $employee->id)->where('status', 'aktif');

        return view('pegawai.dashboard', [
            'employee' => $employee,
            'todaySchedules' => $schedules()->whereDate('work_date', $today)->orderBy('start_time')->get(),
            'upcoming' => $schedules()->whereDate('work_date', '>', $today)->whereDate('work_date', '<=', $today->copy()->addDays(7))
                ->orderBy('work_date')->orderBy('start_time')->get(),
            'attendanceToday' => AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $today)->first(),
            'monthSummary' => AttendanceRecord::where('employee_id', $employee->id)
                ->whereBetween('work_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'pendingCount' => ScheduleRequest::where('employee_id', $employee->id)->where('status', 'menunggu')->count()
                + LeaveRequest::where('employee_id', $employee->id)->where('status', 'menunggu')->count(),
            'lastRequest' => ScheduleRequest::where('employee_id', $employee->id)->latest()->first(),
            'announcements' => Announcement::whereNotNull('published_at')->where('published_at', '<=', now())
                ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $employee->department_id))
                ->latest('published_at')->limit(3)->get(),
        ]);
    }
}
