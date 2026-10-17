<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Services\ActivityLogger;
use App\Services\LeaveService;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    private function model(string $type): string
    {
        return match ($type) {
            'leave' => LeaveRequest::class,
            'overtime' => OvertimeRequest::class,
            default => abort(404),
        };
    }

    private function label(string $type): string
    {
        return $type === 'leave' ? 'Cuti / Izin / Sakit' : 'Lembur';
    }

    public function index(Request $request, string $type)
    {
        $status = $request->query('status');

        return view('admin.approvals.index', [
            'type' => $type, 'label' => $this->label($type), 'status' => $status,
            'items' => $this->model($type)::with(['employee', 'processor'])
                ->when(in_array($status, ['menunggu', 'disetujui', 'ditolak', 'dibatalkan']), fn ($q) => $q->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function approve(Request $request, string $type, int $id)
    {
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);
        $model = $this->model($type);

        $result = DB::transaction(function () use ($model, $id, $data, $request, $type) {
            $item = $model::lockForUpdate()->findOrFail($id);
            if ($item->status !== 'menunggu') {
                return false;
            }
            $item->update(['status' => 'disetujui', 'processed_by' => $request->user()->id, 'processed_at' => now(), 'decision_note' => $data['decision_note'] ?? null]);
            if ($type === 'leave') {
                LeaveService::applyApproved($item);
            }
            Notifier::toEmployee($item->employee, 'Pengajuan disetujui', "Pengajuan {$this->label($type)} Anda disetujui.", route('pegawai.requests.index', $type));
            ActivityLogger::log("{$type}.approved", "Menyetujui pengajuan {$this->label($type)} {$item->employee->full_name}", $item);
            return true;
        });

        return back()->with($result ? 'success' : 'error', $result ? 'Pengajuan disetujui.' : 'Pengajuan sudah diproses sebelumnya.');
    }

    public function reject(Request $request, string $type, int $id)
    {
        $data = $request->validate(['decision_note' => ['required', 'string', 'max:500']], ['decision_note.required' => 'Alasan penolakan wajib diisi.']);
        $model = $this->model($type);

        $result = DB::transaction(function () use ($model, $id, $data, $request, $type) {
            $item = $model::lockForUpdate()->findOrFail($id);
            if ($item->status !== 'menunggu') {
                return false;
            }
            $item->update(['status' => 'ditolak', 'processed_by' => $request->user()->id, 'processed_at' => now(), 'decision_note' => $data['decision_note']]);
            Notifier::toEmployee($item->employee, 'Pengajuan ditolak', 'Alasan: '.$data['decision_note'], route('pegawai.requests.index', $type));
            ActivityLogger::log("{$type}.rejected", "Menolak pengajuan {$this->label($type)} {$item->employee->full_name}", $item, ['note' => $data['decision_note']]);
            return true;
        });

        return back()->with($result ? 'success' : 'error', $result ? 'Pengajuan ditolak.' : 'Pengajuan sudah diproses sebelumnya.');
    }
}
