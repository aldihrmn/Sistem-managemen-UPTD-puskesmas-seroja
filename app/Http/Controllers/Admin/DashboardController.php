<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleRequest;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = today();

        $attendance = AttendanceRecord::whereDate('work_date', $today)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $chart = collect(range(5, 0))->map(function ($i) {
            $m = now()->startOfMonth()->subMonths($i);
            return [
                'label' => $m->translatedFormat('M'),
                'count' => AttendanceRecord::whereBetween('work_date', [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()])
                    ->whereIn('status', ['hadir', 'terlambat'])->count(),
            ];
        });

        return view('admin.dashboard', [
            'totalEmployees' => Employee::count(),
            'activeEmployees' => Employee::where('is_active', true)->count(),
            'departments' => Department::withCount(['employees' => fn ($q) => $q->where('is_active', true)])->orderBy('name')->get(),
            'scheduledToday' => EmployeeSchedule::whereDate('work_date', $today)->where('status', 'aktif')->distinct()->count('employee_id'),
            'attendance' => $attendance,
            'pendingRequests' => ScheduleRequest::where('status', 'menunggu')->count(),
            'todaySchedules' => EmployeeSchedule::with(['employee.department', 'shift'])
                ->whereDate('work_date', $today)->where('status', 'aktif')->orderBy('start_time')->limit(10)->get(),
            'latestRequests' => ScheduleRequest::with('employee')->latest()->limit(5)->get(),
            'announcements' => Announcement::whereNotNull('published_at')->where('published_at', '<=', now())
                ->latest('published_at')->limit(3)->get(),
            'activities' => ActivityLog::with('user')->latest()->limit(6)->get(),
            'chart' => $chart,
        ]);
    }
}
