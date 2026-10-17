<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function payload(array $o = []): array
    {
        return array_merge(['full_name' => 'Siti Contoh', 'nip' => null, 'gender' => 'P', 'is_active' => 1], $o);
    }

    public function test_admin_dashboard_renders_with_empty_database(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('Belum ada jadwal hari ini');
    }

    public function test_pegawai_dashboard_without_employee_record_renders(): void
    {
        $u = User::factory()->create(['role' => 'pegawai', 'is_active' => true]);
        $this->actingAs($u)->get(route('pegawai.dashboard'))->assertOk()->assertSee('belum dihubungkan');
    }

    public function test_pegawai_dashboard_shows_own_schedule(): void
    {
        $u = User::factory()->create(['role' => 'pegawai', 'is_active' => true]);
        $e = Employee::create(['user_id' => $u->id, 'full_name' => 'Budi', 'is_active' => true]);
        EmployeeSchedule::create(['employee_id' => $e->id, 'work_date' => today(), 'start_time' => '08:00', 'end_time' => '12:00', 'location' => 'Poli Umum', 'status' => 'aktif']);
        $this->actingAs($u)->get(route('pegawai.dashboard'))->assertOk()->assertSee('Poli Umum');
    }

    public function test_pegawai_cannot_access_employee_management(): void
    {
        $u = User::factory()->create(['role' => 'pegawai', 'is_active' => true]);
        $this->actingAs($u)->get(route('admin.employees.index'))->assertForbidden();
        $this->actingAs($u)->post(route('admin.employees.store'), $this->payload())->assertForbidden();
    }

    public function test_admin_creates_employee_with_login_account(): void
    {
        $this->actingAs($this->admin())->post(route('admin.employees.store'), $this->payload([
            'email' => 'siti@contoh.test', 'create_account' => 1, 'password' => 'rahasia123',
        ]))->assertRedirect();

        $this->assertDatabaseHas('employees', ['full_name' => 'Siti Contoh']);
        $user = User::where('email', 'siti@contoh.test')->first();
        $this->assertSame('pegawai', $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'employee.created']);
    }

    public function test_nip_is_optional(): void
    {
        $this->actingAs($this->admin())->post(route('admin.employees.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_duplicate_nip_and_email_are_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.employees.store'), $this->payload(['nip' => '123', 'email' => 'a@x.test']));
        $this->actingAs($admin)->post(route('admin.employees.store'), $this->payload(['full_name' => 'Lain', 'nip' => '123', 'email' => 'a@x.test']))
            ->assertSessionHasErrors(['nip', 'email']);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_account_requires_email_and_password(): void
    {
        $this->actingAs($this->admin())->post(route('admin.employees.store'), $this->payload(['create_account' => 1]))
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_search_filters_employees(): void
    {
        Employee::create(['full_name' => 'Siti Rahma', 'is_active' => true]);
        Employee::create(['full_name' => 'Budi Santoso', 'is_active' => true]);
        $this->actingAs($this->admin())->get(route('admin.employees.index', ['q' => 'Siti']))
            ->assertOk()->assertSee('Siti Rahma')->assertDontSee('Budi Santoso');
    }

    public function test_status_filter_and_empty_state(): void
    {
        Employee::create(['full_name' => 'Nonaktif Satu', 'is_active' => false]);
        $this->actingAs($this->admin())->get(route('admin.employees.index', ['status' => 'aktif']))
            ->assertOk()->assertDontSee('Nonaktif Satu')->assertSee('Belum ada data pegawai');
    }

    public function test_update_syncs_account_name(): void
    {
        $u = User::factory()->create(['role' => 'pegawai', 'email' => 'p@x.test']);
        $e = Employee::create(['user_id' => $u->id, 'full_name' => 'Lama', 'email' => 'p@x.test', 'is_active' => true]);
        $this->actingAs($this->admin())->put(route('admin.employees.update', $e), $this->payload(['full_name' => 'Baru', 'email' => 'p@x.test']))
            ->assertRedirect();
        $this->assertSame('Baru', $u->fresh()->name);
    }

    public function test_toggle_deactivates_employee_and_account(): void
    {
        $u = User::factory()->create(['role' => 'pegawai', 'is_active' => true]);
        $e = Employee::create(['user_id' => $u->id, 'full_name' => 'X', 'is_active' => true]);
        $this->actingAs($this->admin())->patch(route('admin.employees.toggle', $e))->assertRedirect();
        $this->assertFalse($e->fresh()->is_active);
        $this->assertFalse($u->fresh()->is_active);
    }

    public function test_reset_password(): void
    {
        $u = User::factory()->create(['role' => 'pegawai']);
        $e = Employee::create(['user_id' => $u->id, 'full_name' => 'X', 'is_active' => true]);
        $this->actingAs($this->admin())->post(route('admin.employees.reset-password', $e), [
            'password' => 'passbaru123', 'password_confirmation' => 'passbaru123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('passbaru123', $u->fresh()->password));
    }

    public function test_master_data_create_duplicate_and_toggle(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.master.store', 'department'), ['name' => 'Gizi'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.master.store', 'department'), ['name' => 'Gizi'])->assertSessionHasErrors('name');

        $d = Department::first();
        $this->actingAs($admin)->patch(route('admin.master.toggle', ['department', $d->id]))->assertRedirect();
        $this->assertFalse($d->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.master.store', 'position'), ['name' => 'Perawat'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('admin.master.index', 'position'))->assertOk()->assertSee('Perawat');
    }
}
