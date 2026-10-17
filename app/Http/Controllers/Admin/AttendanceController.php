<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private const STATUSES = ['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpa'];

    public function index(Request $request)
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $from = $request->query('from', today()->toDateString());
        $to = $request->query('to', $from);

        $base = AttendanceRecord::query()
            ->whereBetween('work_date', [$from, $to])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->department_id)))
            ->when(in_array($request->status, self::STATUSES), fn ($q) => $q->where('status', $request->status));

        return view('admin.attendance.index', [
            'records' => (clone $base)->with(['employee.department', 'schedule', 'corrector'])->orderByDesc('work_date')->orderBy('check_in')->paginate(20)->withQueryString(),
            'summary' => (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'from' => $from, 'to' => $to, 'statuses' => self::STATUSES,
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'employees' => Employee::where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Alasan pencatatan manual wajib diisi.']);

        $schedule = EmployeeSchedule::where('employee_id', $d['employee_id'])->where('status', 'aktif')
            ->whereDate('work_date', $d['work_date'])
            ->whereDoesntHave('attendance')->orderBy('start_time')->first();

        $exists = AttendanceRecord::where('employee_id', $d['employee_id'])->whereDate('work_date', $d['work_date'])
            ->when($schedule, fn ($q) => $q->where('schedule_id', $schedule->id), fn ($q) => $q->whereNull('schedule_id'))->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Catatan absensi untuk pegawai dan tanggal tersebut sudah ada. Gunakan koreksi.');
        }

        $rec = AttendanceRecord::create([
            'employee_id' => $d['employee_id'], 'schedule_id' => $schedule?->id, 'work_date' => $d['work_date'], 'status' => $d['status'],
            'check_in' => $d['check_in'] ?? null, 'check_out' => $d['check_out'] ?? null,
            'corrected_by' => $request->user()->id, 'correction_reason' => $d['reason'], 'notes' => 'Dicatat manual oleh admin',
        ]);
        ActivityLogger::log('attendance.created', 'Mencatat absensi manual untuk '.$rec->employee->full_name, $rec, ['reason' => $d['reason']]);

        return back()->with('success', 'Absensi manual dicatat.');
    }

    public function update(Request $request, AttendanceRecord $record)
    {
        $d = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Alasan koreksi wajib diisi.']);

        $before = $record->only(['status', 'check_in', 'check_out']);
        $record->update([
            'status' => $d['status'], 'check_in' => $d['check_in'] ?? null, 'check_out' => $d['check_out'] ?? null,
            'corrected_by' => $request->user()->id, 'correction_reason' => $d['reason'],
        ]);
        ActivityLogger::log('attendance.corrected', 'Koreksi absensi '.$record->employee->full_name.' '.$record->work_date->format('d/m/Y'), $record,
            ['before' => $before, 'after' => $record->only(['status', 'check_in', 'check_out']), 'reason' => $d['reason']]);

        return back()->with('success', 'Koreksi absensi tersimpan.');
    }
}
