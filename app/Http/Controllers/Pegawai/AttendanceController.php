<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\EmployeeSchedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ResolvesEmployee;

    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);
        $me = $this->me();
        $month = Carbon::createFromFormat('Y-m-d', ($request->query('month') ?: now()->format('Y-m')).'-01')->startOfMonth();

        $records = AttendanceRecord::with('schedule')->where('employee_id', $me->id)
            ->whereBetween('work_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('work_date')->get();

        return view('pegawai.attendance.index', [
            'month' => $month, 'records' => $records, 'summary' => $records->countBy('status'),
            'todaySchedules' => EmployeeSchedule::with(['shift', 'attendance'])->where('employee_id', $me->id)
                ->where('status', 'aktif')->whereDate('work_date', today())->orderBy('start_time')->get(),
        ]);
    }

    public function checkIn(EmployeeSchedule $schedule)
    {
        $me = $this->me();
        abort_unless($schedule->employee_id === $me->id, 403);

        if ($schedule->status !== 'aktif' || ! $schedule->work_date->isToday()) {
            return back()->with('error', 'Absen masuk hanya untuk jadwal aktif hari ini.');
        }
        if (AttendanceRecord::where('schedule_id', $schedule->id)->exists()) {
            return back()->with('error', 'Anda sudah tercatat untuk jadwal ini.');
        }

        $tolerance = (int) Setting::get('late_tolerance_minutes', 15);
        $deadline = Carbon::parse($schedule->work_date->toDateString().' '.$schedule->start_time)->addMinutes($tolerance);
        $late = now()->gt($deadline);

        AttendanceRecord::create([
            'employee_id' => $me->id, 'schedule_id' => $schedule->id, 'work_date' => today()->toDateString(),
            'check_in' => now()->format('H:i:s'), 'status' => $late ? 'terlambat' : 'hadir',
        ]);

        return back()->with('success', $late ? 'Absen masuk tercatat (terlambat).' : 'Absen masuk tercatat.');
    }

    public function checkOut(EmployeeSchedule $schedule)
    {
        $me = $this->me();
        abort_unless($schedule->employee_id === $me->id, 403);

        $rec = AttendanceRecord::where('schedule_id', $schedule->id)->first();
        if (! $rec || ! $rec->check_in) {
            return back()->with('error', 'Anda belum absen masuk untuk jadwal ini.');
        }
        if ($rec->check_out) {
            return back()->with('error', 'Absen pulang sudah tercatat.');
        }
        $rec->update(['check_out' => now()->format('H:i:s')]);

        return back()->with('success', 'Absen pulang tercatat.');
    }
}
