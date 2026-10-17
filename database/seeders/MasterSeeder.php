<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Database\Seeder;

/** Data awal CONTOH (fiktif) — admin dapat mengubahnya dari aplikasi. */
class MasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pelayanan Umum', 'KIA / KB', 'Farmasi', 'Laboratorium', 'Administrasi'] as $n) {
            Department::firstOrCreate(['name' => $n]);
        }
        foreach (['Dokter', 'Perawat', 'Bidan', 'Apoteker', 'Analis Laboratorium', 'Staf Administrasi'] as $n) {
            Position::firstOrCreate(['name' => $n]);
        }

        $shifts = [
            ['Jam Kerja Reguler', 'reguler', '08:00', '15:00', false, '#16a34a'],
            ['Jadwal Pelayanan', 'pelayanan', '08:00', '12:00', false, '#0ea5e9'],
            ['Piket', 'piket', '15:00', '21:00', false, '#f59e0b'],
            ['Rapat', 'rapat', '13:00', '14:30', false, '#8b5cf6'],
        ];
        foreach ($shifts as [$name, $type, $s, $e, $cm, $color]) {
            Shift::firstOrCreate(['name' => $name], [
                'type' => $type, 'start_time' => $s, 'end_time' => $e,
                'crosses_midnight' => $cm, 'color' => $color,
            ]);
        }

        Setting::firstOrCreate(['key' => 'institution_name'], ['value' => 'Puskesmas Seroja']);
        Setting::firstOrCreate(['key' => 'late_tolerance_minutes'], ['value' => '15']);
    }
}
