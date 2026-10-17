<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Shift;
use Illuminate\Database\Seeder;

/** Data contoh fiktif agar dashboard & laporan langsung terisi. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $shift = Shift::where('name', 'Jam Kerja Reguler')->first();
        if (! $shift) {
            return;
        }

        foreach (Employee::where('is_active', true)->get() as $e) {
            foreach (range(-10, 10) as $offset) {
                $d = today()->addDays($offset);
                if ($d->isWeekend()) {
                    continue;
                }

                $s = EmployeeSchedule::firstOrCreate(
                    ['employee_id' => $e->id, 'work_date' => $d->toDateString()],
                    ['shift_id' => $shift->id, 'start_time' => $shift->start_time, 'end_time' => $shift->end_time,
                     'location' => $e->department?->name ?? 'Puskesmas', 'status' => 'aktif']
                );

                if ($offset < 0) {
                    $late = $offset % 4 === 0;
                    AttendanceRecord::firstOrCreate(['employee_id' => $e->id, 'schedule_id' => $s->id], [
                        'work_date' => $d->toDateString(), 'status' => $late ? 'terlambat' : 'hadir',
                        'check_in' => $late ? '08:40:00' : '07:55:00', 'check_out' => '15:05:00',
                    ]);
                }
            }
        }

        Announcement::firstOrCreate(['title' => 'Selamat datang di sistem pegawai'], [
            'body' => 'Silakan periksa jadwal Anda dan ajukan perubahan melalui menu Pengajuan Jadwal.', 'published_at' => now(),
        ]);
    }
}
