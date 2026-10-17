<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class SchedulingBase extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    protected function emp(string $name = 'Pegawai', bool $withUser = true): Employee
    {
        $u = $withUser ? User::factory()->create(['role' => 'pegawai', 'is_active' => true]) : null;
        return Employee::create(['user_id' => $u?->id, 'full_name' => $name, 'is_active' => true]);
    }

    protected function sched(Employee $e, string $date, string $s = '08:00', string $end = '12:00', bool $crosses = false): EmployeeSchedule
    {
        return EmployeeSchedule::create(['employee_id' => $e->id, 'work_date' => $date, 'start_time' => $s, 'end_time' => $end,
            'crosses_midnight' => $crosses, 'status' => 'aktif']);
    }
}
