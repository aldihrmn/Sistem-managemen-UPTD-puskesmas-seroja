<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Akun DEMO pengembangan. Password: "password" — WAJIB diganti di produksi. */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@seroja.test'], [
            'name' => 'Admin Demo', 'password' => 'password', 'role' => 'admin', 'is_active' => true,
        ]);

        $demo = [
            ['Siti Rahma (Demo)', 'pegawai1@seroja.test', 'Perawat', 'Pelayanan Umum', 'P'],
            ['Budi Santoso (Demo)', 'pegawai2@seroja.test', 'Dokter', 'Pelayanan Umum', 'L'],
            ['Dewi Lestari (Demo)', 'pegawai3@seroja.test', 'Bidan', 'KIA / KB', 'P'],
        ];

        foreach ($demo as [$name, $email, $pos, $dept, $g]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, 'password' => 'password', 'role' => 'pegawai', 'is_active' => true,
            ]);
            Employee::updateOrCreate(['user_id' => $user->id], [
                'full_name' => $name, 'email' => $email, 'gender' => $g, 'profession' => $pos,
                'position_id' => Position::where('name', $pos)->value('id'),
                'department_id' => Department::where('name', $dept)->value('id'),
                'employment_status' => 'Contoh', 'start_date' => now()->subYear(), 'is_active' => true,
            ]);
        }
    }
}
