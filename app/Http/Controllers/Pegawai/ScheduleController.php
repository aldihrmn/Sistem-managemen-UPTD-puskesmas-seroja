<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\EmployeeSchedule;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ResolvesEmployee;

    public function index(Request $request)
    {
        $request->validate(['date' => 'nullable|date', 'view' => 'nullable|in:day,week,month', 'scope' => 'nullable|in:mine,team,history']);
        $me = $this->me();
        $scope = $request->query('scope', 'mine');
        $view = $request->query('view', 'week');
        $date = $request->filled('date') ? Carbon::parse($request->date)->startOfDay() : today();
        [$from, $to] = ScheduleService::range($view, $date);

        if ($scope === 'history') {
            return view('pegawai.schedules.index', [
                'scope' => $scope, 'view' => $view, 'date' => $date, 'from' => $from, 'to' => $to,
                'grouped' => collect(), 'prev' => $date, 'next' => $date,
                'history' => EmployeeSchedule::with('shift')->where('employee_id', $me->id)
                    ->whereDate('work_date', '<', today())->orderByDesc('work_date')->paginate(15)->withQueryString(),
            ]);
        }

        $schedules = EmployeeSchedule::with(['shift', 'employee'])->where('status', 'aktif')
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->when($scope === 'team' && $me->department_id,
                fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $me->department_id)->where('is_active', true)),
                fn ($q) => $q->where('employee_id', $me->id))
            ->orderBy('work_date')->orderBy('start_time')->get();

        return view('pegawai.schedules.index', [
            'scope' => $scope, 'view' => $view, 'date' => $date, 'from' => $from, 'to' => $to, 'history' => null,
            'grouped' => $schedules->groupBy(fn ($s) => $s->work_date->toDateString()),
            'prev' => ScheduleService::step($view, $date, -1), 'next' => ScheduleService::step($view, $date, 1), 'me' => $me,
        ]);
    }
}
