<?php

namespace App\Services;

use App\Exceptions\ScheduleConflictException;
use App\Models\EmployeeSchedule;
use App\Models\ScheduleRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScheduleRequestService
{
    /** Terapkan perubahan jadwal. Harus dipanggil di dalam transaksi. Mengembalikan [before, after]. */
    private function applyChange(ScheduleRequest $r): array
    {
        $fail = fn (array $p) => throw new ScheduleConflictException(implode(' ', $p));

        switch ($r->type) {
            case 'ubah':
                $s = EmployeeSchedule::lockForUpdate()->find($r->schedule_id);
                if (! $s || $s->status !== 'aktif') {
                    $fail(['Jadwal asal sudah tidak aktif atau tidak ditemukan.']);
                }
                $before = ScheduleService::snapshot($s);
                $date = $r->requested_date->toDateString();
                if ($p = ScheduleService::problems($s->employee_id, $date, $r->requested_start, $r->requested_end, false, [$s->id])) {
                    $fail($p);
                }
                $s->update(['work_date' => $date, 'start_time' => $r->requested_start, 'end_time' => $r->requested_end,
                    'crosses_midnight' => false, 'shift_id' => $r->requested_shift_id ?? $s->shift_id]);
                return [$before, ScheduleService::snapshot($s->fresh())];

            case 'minta':
                $date = $r->requested_date->toDateString();
                if ($p = ScheduleService::problems($r->employee_id, $date, $r->requested_start, $r->requested_end, false)) {
                    $fail($p);
                }
                $s = EmployeeSchedule::create([
                    'employee_id' => $r->employee_id, 'shift_id' => $r->requested_shift_id, 'work_date' => $date,
                    'start_time' => $r->requested_start, 'end_time' => $r->requested_end, 'crosses_midnight' => false,
                    'location' => 'Permintaan jadwal pegawai', 'notes' => $r->reason, 'status' => 'aktif', 'created_by' => auth()->id(),
                ]);
                return [null, ScheduleService::snapshot($s)];

            case 'tukar':
                $s1 = EmployeeSchedule::lockForUpdate()->find($r->schedule_id);
                $s2 = EmployeeSchedule::lockForUpdate()->find($r->target_schedule_id);
                if (! $s1 || ! $s2 || $s1->status !== 'aktif' || $s2->status !== 'aktif') {
                    $fail(['Salah satu jadwal yang akan ditukar sudah tidak aktif.']);
                }
                $a = $s1->employee_id;
                $b = $s2->employee_id;
                $before = [ScheduleService::snapshot($s1), ScheduleService::snapshot($s2)];
                $p = array_merge(
                    ScheduleService::problems($b, $s1->work_date->toDateString(), $s1->start_time, $s1->end_time, (bool) $s1->crosses_midnight, [$s1->id, $s2->id]),
                    ScheduleService::problems($a, $s2->work_date->toDateString(), $s2->start_time, $s2->end_time, (bool) $s2->crosses_midnight, [$s1->id, $s2->id])
                );
                if ($p) {
                    $fail($p);
                }
                $s1->update(['employee_id' => $b]);
                $s2->update(['employee_id' => $a]);
                return [$before, [ScheduleService::snapshot($s1->fresh()), ScheduleService::snapshot($s2->fresh())]];

            case 'batal':
                $s = EmployeeSchedule::lockForUpdate()->find($r->schedule_id);
                if (! $s || $s->status !== 'aktif') {
                    $fail(['Jadwal sudah tidak aktif atau tidak ditemukan.']);
                }
                $before = ScheduleService::snapshot($s);
                $s->update(['status' => 'dibatalkan']);
                return [$before, ScheduleService::snapshot($s->fresh())];
        }

        $fail(['Jenis pengajuan tidak dikenal.']);
    }

    /** Pratinjau benturan tanpa menyimpan apa pun. Mengembalikan daftar masalah. */
    public function preview(ScheduleRequest $r): array
    {
        DB::beginTransaction();
        try {
            $this->applyChange($r);
            return [];
        } catch (ScheduleConflictException $e) {
            return [$e->getMessage()];
        } finally {
            DB::rollBack();
        }
    }

    public function approve(ScheduleRequest $req, User $admin, ?string $note): ScheduleRequest
    {
        return DB::transaction(function () use ($req, $admin, $note) {
            $r = ScheduleRequest::lockForUpdate()->findOrFail($req->id);
            if ($r->status !== 'menunggu') {
                throw new ScheduleConflictException('Pengajuan ini sudah diproses sebelumnya.');
            }

            [$before, $after] = $this->applyChange($r);

            $r->update([
                'status' => 'disetujui', 'processed_by' => $admin->id, 'processed_at' => now(), 'decision_note' => $note,
                'before_snapshot' => $before, 'after_snapshot' => $after,
            ]);

            Notifier::toEmployee($r->employee, 'Pengajuan jadwal disetujui', 'Pengajuan '.strtolower(ScheduleRequest::TYPES[$r->type]).' Anda disetujui. Jadwal telah diperbarui.', route('pegawai.schedules.index'));
            if ($r->type === 'tukar') {
                Notifier::toEmployee($r->targetEmployee, 'Jadwal Anda ditukar', 'Jadwal Anda ditukar dengan '.$r->employee->full_name.' atas persetujuan admin.', route('pegawai.schedules.index'));
            }
            ActivityLogger::log('schedule_request.approved', "Menyetujui pengajuan jadwal #{$r->id} ({$r->employee->full_name})", $r, ['before' => $before, 'after' => $after]);

            return $r;
        });
    }

    public function reject(ScheduleRequest $req, User $admin, string $note): ScheduleRequest
    {
        return DB::transaction(function () use ($req, $admin, $note) {
            $r = ScheduleRequest::lockForUpdate()->findOrFail($req->id);
            if ($r->status !== 'menunggu') {
                throw new ScheduleConflictException('Pengajuan ini sudah diproses sebelumnya.');
            }

            $r->update(['status' => 'ditolak', 'processed_by' => $admin->id, 'processed_at' => now(), 'decision_note' => $note]);
            Notifier::toEmployee($r->employee, 'Pengajuan jadwal ditolak', "Alasan: {$note}", route('pegawai.schedule-requests.index'));
            ActivityLogger::log('schedule_request.rejected', "Menolak pengajuan jadwal #{$r->id} ({$r->employee->full_name})", $r, ['note' => $note]);

            return $r;
        });
    }
}
