<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Pengajuan cuti/izin/sakit (leave) dan lembur (overtime) oleh pegawai. */
class RequestController extends Controller
{
    use ResolvesEmployee;

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

    public function index(string $type)
    {
        return view('pegawai.requests.index', [
            'type' => $type, 'label' => $this->label($type),
            'items' => $this->model($type)::with('processor')->where('employee_id', $this->me()->id)->latest()->paginate(10),
        ]);
    }

    public function create(string $type)
    {
        $this->model($type);

        return view('pegawai.requests.create', ['type' => $type, 'label' => $this->label($type)]);
    }

    public function store(Request $request, string $type)
    {
        $me = $this->me();
        $model = $this->model($type);

        if ($type === 'leave') {
            $d = $request->validate([
                'type' => ['required', 'in:cuti,izin,sakit'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'reason' => ['required', 'string', 'max:1000'], 'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            ], ['end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 'reason.required' => 'Alasan wajib diisi.']);

            $overlap = LeaveRequest::where('employee_id', $me->id)->whereIn('status', ['menunggu', 'disetujui'])
                ->whereDate('start_date', '<=', $d['end_date'])->whereDate('end_date', '>=', $d['start_date'])->exists();
            if ($overlap) {
                return back()->withInput()->with('error', 'Sudah ada pengajuan cuti/izin/sakit yang bertumpuk pada periode tersebut.');
            }
            $d['attachment'] = $request->hasFile('attachment') ? $request->file('attachment')->store('attachments', 'local') : null;
        } else {
            $d = $request->validate([
                'work_date' => ['required', 'date'], 'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'], 'reason' => ['required', 'string', 'max:1000'],
            ], ['end_time.after' => 'Jam selesai harus lebih akhir dari jam mulai.', 'reason.required' => 'Alasan wajib diisi.']);

            $overlap = OvertimeRequest::where('employee_id', $me->id)->whereIn('status', ['menunggu', 'disetujui'])
                ->whereDate('work_date', $d['work_date'])->where('start_time', '<', $d['end_time'])->where('end_time', '>', $d['start_time'])->exists();
            if ($overlap) {
                return back()->withInput()->with('error', 'Sudah ada pengajuan lembur yang bertumpuk pada waktu tersebut.');
            }
        }

        $model::create($d + ['employee_id' => $me->id, 'status' => 'menunggu']);

        Notifier::toEmployee($me, 'Pengajuan diterima', "Pengajuan {$this->label($type)} Anda diterima dan menunggu persetujuan.", route('pegawai.requests.index', $type));
        Notifier::toAdmins('Pengajuan baru', "{$me->full_name} mengajukan {$this->label($type)}.", route('admin.approvals.index', $type));

        return redirect()->route('pegawai.requests.index', $type)->with('success', 'Pengajuan berhasil dikirim.');
    }

    public function cancel(string $type, int $id)
    {
        $item = $this->model($type)::findOrFail($id);
        abort_unless($item->employee_id === $this->me()->id, 403);

        if ($item->status !== 'menunggu') {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat dibatalkan.');
        }
        $item->update(['status' => 'dibatalkan']);

        return back()->with('success', 'Pengajuan dibatalkan.');
    }
}
