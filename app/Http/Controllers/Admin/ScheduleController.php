<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleFormRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Services\ActivityLogger;
use App\Services\Notifier;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['date' => 'nullable|date', 'view' => 'nullable|in:day,week,month']);
        $view = $request->query('view', 'day');
        $date = $request->filled('date') ? Carbon::parse($request->date)->startOfDay() : today();
        [$from, $to] = ScheduleService::range($view, $date);
        $q = trim((string) $request->query('q'));

        $schedules = EmployeeSchedule::with(['employee.department', 'shift'])
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('employee_id'), fn ($b) => $b->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn ($b) => $b->whereHas('employee', fn ($e) => $e->where('department_id', $request->department_id)))
            ->when($request->filled('shift_id'), fn ($b) => $b->where('shift_id', $request->shift_id))
            ->when($q !== '', fn ($b) => $b->whereHas('employee', fn ($e) => $e->where('full_name', 'like', "%{$q}%")))
            ->orderBy('work_date')->orderBy('start_time')->get();

        $days = [];
        if ($view === 'week') {
            $days = iterator_to_array(CarbonPeriod::create($from, $to));
        } elseif ($view === 'month') {
            $days = iterator_to_array(CarbonPeriod::create($from->copy()->startOfWeek(), $to->copy()->endOfWeek()));
        }

        return view('admin.schedules.index', [
            'view' => $view, 'date' => $date, 'from' => $from, 'to' => $to, 'schedules' => $schedules, 'days' => $days,
            'prev' => ScheduleService::step($view, $date, -1), 'next' => ScheduleService::step($view, $date, 1),
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'employees' => Employee::where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
            'shifts' => Shift::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.schedules.create', $this->formData() + ['defaultDate' => $request->query('date', today()->toDateString())]);
    }

    public function store(ScheduleFormRequest $request)
    {
        $d = $request->validated();
        $crosses = $request->boolean('crosses_midnight');
        $employeeIds = array_unique($d['employee_ids']);

        $problems = [];
        foreach ($employeeIds as $id) {
            $problems = array_merge($problems, ScheduleService::problems((int) $id, $d['work_date'], $d['start_time'], $d['end_time'], $crosses));
        }
        if ($problems) {
            return back()->withInput()->with('conflicts', $problems);
        }

        DB::transaction(function () use ($employeeIds, $d, $crosses, $request) {
            foreach ($employeeIds as $id) {
                $s = EmployeeSchedule::create([
                    'employee_id' => $id, 'shift_id' => $d['shift_id'] ?? null, 'work_date' => $d['work_date'],
                    'start_time' => $d['start_time'], 'end_time' => $d['end_time'], 'crosses_midnight' => $crosses,
                    'location' => $d['location'] ?? null, 'notes' => $d['notes'] ?? null, 'status' => 'aktif', 'created_by' => $request->user()->id,
                ]);
                $s->load('employee');
                ActivityLogger::log('schedule.created', "Membuat jadwal {$s->employee->full_name} ".$s->work_date->format('d/m/Y'), $s);
                Notifier::toEmployee($s->employee, 'Jadwal baru', 'Anda dijadwalkan pada '.$s->work_date->translatedFormat('d F Y').' pukul '.$d['start_time'].'–'.$d['end_time'].'.', route('pegawai.schedules.index'));
            }
        });

        return redirect()->route('admin.schedules.index', ['date' => $d['work_date']])->with('success', count($employeeIds).' jadwal berhasil dibuat.');
    }

    public function show(EmployeeSchedule $schedule)
    {
        return view('admin.schedules.show', ['schedule' => $schedule->load(['employee.department', 'shift', 'creator']),
            'attendance' => \App\Models\AttendanceRecord::where('schedule_id', $schedule->id)->first()]);
    }

    public function edit(EmployeeSchedule $schedule)
    {
        return view('admin.schedules.edit', $this->formData() + ['schedule' => $schedule]);
    }

    public function update(ScheduleFormRequest $request, EmployeeSchedule $schedule)
    {
        $d = $request->validated();
        $crosses = $request->boolean('crosses_midnight');

        if ($problems = ScheduleService::problems((int) $d['employee_id'], $d['work_date'], $d['start_time'], $d['end_time'], $crosses, [$schedule->id])) {
            return back()->withInput()->with('conflicts', $problems);
        }

        DB::transaction(function () use ($schedule, $d, $crosses) {
            $before = ScheduleService::snapshot($schedule);
            $schedule->update([
                'employee_id' => $d['employee_id'], 'shift_id' => $d['shift_id'] ?? null, 'work_date' => $d['work_date'],
                'start_time' => $d['start_time'], 'end_time' => $d['end_time'], 'crosses_midnight' => $crosses,
                'location' => $d['location'] ?? null, 'notes' => $d['notes'] ?? null,
            ]);
            $schedule->load('employee');
            ActivityLogger::log('schedule.updated', "Mengubah jadwal {$schedule->employee->full_name}", $schedule, ['before' => $before, 'after' => ScheduleService::snapshot($schedule)]);
            Notifier::toEmployee($schedule->employee, 'Jadwal diubah', 'Jadwal Anda pada '.$schedule->work_date->translatedFormat('d F Y').' diperbarui.', route('pegawai.schedules.index'));
        });

        return redirect()->route('admin.schedules.show', $schedule)->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function cancel(EmployeeSchedule $schedule)
    {
        if ($schedule->status === 'dibatalkan') {
            return back()->with('error', 'Jadwal sudah dibatalkan.');
        }

        DB::transaction(function () use ($schedule) {
            $schedule->update(['status' => 'dibatalkan']);
            $schedule->load('employee');
            ActivityLogger::log('schedule.cancelled', "Membatalkan jadwal {$schedule->employee->full_name} ".$schedule->work_date->format('d/m/Y'), $schedule);
            Notifier::toEmployee($schedule->employee, 'Jadwal dibatalkan', 'Jadwal Anda pada '.$schedule->work_date->translatedFormat('d F Y').' dibatalkan.', route('pegawai.schedules.index'));
        });

        return back()->with('success', 'Jadwal dibatalkan.');
    }

    private function formData(): array
    {
        return [
            'employees' => Employee::with('department')->where('is_active', true)->orderBy('full_name')->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
