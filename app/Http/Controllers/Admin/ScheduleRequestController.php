<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ScheduleConflictException;
use App\Http\Controllers\Controller;
use App\Models\ScheduleRequest;
use App\Services\ScheduleRequestService;
use Illuminate\Http\Request;

class ScheduleRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        return view('admin.schedule-requests.index', [
            'requests' => ScheduleRequest::with('employee')
                ->when(in_array($status, ['menunggu', 'disetujui', 'ditolak', 'dibatalkan']), fn ($q) => $q->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(ScheduleRequest $scheduleRequest, ScheduleRequestService $svc)
    {
        $scheduleRequest->load(['employee.department', 'schedule.shift', 'targetEmployee', 'targetSchedule', 'processor']);

        return view('admin.schedule-requests.show', [
            'r' => $scheduleRequest,
            'problems' => $scheduleRequest->status === 'menunggu' ? $svc->preview($scheduleRequest) : [],
        ]);
    }

    public function approve(Request $request, ScheduleRequest $scheduleRequest, ScheduleRequestService $svc)
    {
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);

        try {
            $svc->approve($scheduleRequest, $request->user(), $data['decision_note'] ?? null);
        } catch (ScheduleConflictException $e) {
            return back()->with('error', 'Tidak dapat disetujui: '.$e->getMessage());
        }

        return redirect()->route('admin.schedule-requests.show', $scheduleRequest)->with('success', 'Pengajuan disetujui dan jadwal diperbarui.');
    }

    public function reject(Request $request, ScheduleRequest $scheduleRequest, ScheduleRequestService $svc)
    {
        $data = $request->validate(['decision_note' => ['required', 'string', 'max:500']], ['decision_note.required' => 'Alasan penolakan wajib diisi.']);

        try {
            $svc->reject($scheduleRequest, $request->user(), $data['decision_note']);
        } catch (ScheduleConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.schedule-requests.show', $scheduleRequest)->with('success', 'Pengajuan ditolak.');
    }
}
