<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleRequest;
use App\Models\Shift;
use App\Services\ActivityLogger;
use App\Services\Notifier;
use App\Services\ScheduleService;
use Illuminate\Http\Request;

class ScheduleRequestController extends Controller
{
    use ResolvesEmployee;

    public function index()
    {
        return view('pegawai.schedule-requests.index', [
            'requests' => ScheduleRequest::with(['schedule', 'targetEmployee', 'processor'])->where('employee_id', $this->me()->id)->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        $me = $this->me();
        $upcoming = fn () => EmployeeSchedule::with('employee')->where('status', 'aktif')
            ->whereDate('work_date', '>=', today())->whereDate('work_date', '<=', today()->addDays(60))->orderBy('work_date')->orderBy('start_time');

        return view('pegawai.schedule-requests.create', [
            'mySchedules' => $upcoming()->where('employee_id', $me->id)->get(),
            'otherSchedules' => $upcoming()->where('employee_id', '!=', $me->id)
                ->whereHas('employee', fn ($e) => $e->where('is_active', true)->when($me->department_id, fn ($q) => $q->where('department_id', $me->department_id)))->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $me = $this->me();
        $type = $request->validate(['type' => ['required', 'in:ubah,minta,tukar,batal']], ['type.required' => 'Pilih jenis pengajuan.'])['type'];

        $rules = ['reason' => ['required', 'string', 'max:1000'], 'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048']];
        $own = fn ($id) => EmployeeSchedule::where('id', $id)->where('employee_id', $me->id)->where('status', 'aktif')->whereDate('work_date', '>=', today())->exists();

        match ($type) {
            'ubah' => $rules += ['schedule_id' => ['required', 'integer'], 'requested_date' => ['required', 'date', 'after_or_equal:today'],
                'requested_start' => ['required', 'date_format:H:i'], 'requested_end' => ['required', 'date_format:H:i', 'after:requested_start'], 'requested_shift_id' => ['nullable', 'exists:shifts,id']],
            'minta' => $rules += ['requested_date' => ['required', 'date', 'after_or_equal:today'],
                'requested_start' => ['required', 'date_format:H:i'], 'requested_end' => ['required', 'date_format:H:i', 'after:requested_start'], 'requested_shift_id' => ['nullable', 'exists:shifts,id']],
            'tukar' => $rules += ['schedule_id' => ['required', 'integer'], 'target_schedule_id' => ['required', 'integer']],
            'batal' => $rules += ['schedule_id' => ['required', 'integer']],
        };

        $d = $request->validate($rules, [
            'reason.required' => 'Alasan pengajuan wajib diisi.', 'schedule_id.required' => 'Pilih jadwal Anda.',
            'requested_end.after' => 'Jam selesai harus lebih akhir dari jam mulai.', 'target_schedule_id.required' => 'Pilih jadwal pegawai lain yang akan ditukar.',
            'requested_date.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
        ]);

        $schedule = null;
        $target = null;
        if (in_array($type, ['ubah', 'tukar', 'batal'])) {
            if (! $own($d['schedule_id'])) {
                return back()->withInput()->withErrors(['schedule_id' => 'Jadwal tidak valid atau bukan milik Anda.']);
            }
            $schedule = EmployeeSchedule::find($d['schedule_id']);
            if (ScheduleRequest::where('schedule_id', $schedule->id)->where('status', 'menunggu')->exists()) {
                return back()->withInput()->with('error', 'Sudah ada pengajuan yang menunggu untuk jadwal ini.');
            }
        }
        if ($type === 'tukar') {
            $target = EmployeeSchedule::with('employee')->where('id', $d['target_schedule_id'])->where('status', 'aktif')->whereDate('work_date', '>=', today())->first();
            if (! $target || $target->employee_id === $me->id || ! $target->employee->is_active) {
                return back()->withInput()->withErrors(['target_schedule_id' => 'Jadwal tujuan tidak valid.']);
            }
        }

        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('attachments', 'local') : null;

        $r = ScheduleRequest::create([
            'employee_id' => $me->id, 'type' => $type, 'schedule_id' => $schedule?->id, 'target_schedule_id' => $target?->id,
            'target_employee_id' => $target?->employee_id, 'requested_date' => $d['requested_date'] ?? null,
            'requested_start' => $d['requested_start'] ?? null, 'requested_end' => $d['requested_end'] ?? null,
            'requested_shift_id' => $d['requested_shift_id'] ?? null, 'reason' => $d['reason'], 'attachment' => $path, 'status' => 'menunggu',
            'before_snapshot' => $schedule ? ScheduleService::snapshot($schedule) : null,
        ]);

        Notifier::toEmployee($me, 'Pengajuan diterima', 'Pengajuan '.strtolower(ScheduleRequest::TYPES[$type]).' Anda telah diterima dan menunggu persetujuan.', route('pegawai.schedule-requests.index'));
        Notifier::toAdmins('Pengajuan jadwal baru', "{$me->full_name} mengajukan ".strtolower(ScheduleRequest::TYPES[$type]).'.', route('admin.schedule-requests.show', $r));

        return redirect()->route('pegawai.schedule-requests.index')->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function cancel(ScheduleRequest $scheduleRequest)
    {
        abort_unless($scheduleRequest->employee_id === $this->me()->id, 403);

        if ($scheduleRequest->status !== 'menunggu') {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat dibatalkan.');
        }
        $scheduleRequest->update(['status' => 'dibatalkan']);
        ActivityLogger::log('schedule_request.cancelled', "Pegawai membatalkan pengajuan jadwal #{$scheduleRequest->id}", $scheduleRequest);

        return back()->with('success', 'Pengajuan dibatalkan.');
    }
}
