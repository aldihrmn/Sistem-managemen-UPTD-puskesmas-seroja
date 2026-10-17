<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Setting;
use App\Services\ReportService;

class AttendanceLeaveReportTest extends SchedulingBase
{
    public function test_check_in_marks_late_and_blocks_double_and_foreign_access(): void
    {
        Setting::create(['key' => 'late_tolerance_minutes', 'value' => '0']);
        $a = $this->emp('A');
        $b = $this->emp('B');
        $s = $this->sched($a, today()->toDateString(), '00:00', '23:59');

        $this->actingAs($b->user)->post(route('pegawai.attendance.check-in', $s))->assertForbidden();
        $this->actingAs($a->user)->post(route('pegawai.attendance.check-in', $s))->assertSessionHas('success');
        $this->assertSame('terlambat', AttendanceRecord::first()->status);
        $this->actingAs($a->user)->post(route('pegawai.attendance.check-in', $s))->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 1);

        $this->actingAs($a->user)->post(route('pegawai.attendance.check-out', $s))->assertSessionHas('success');
        $this->assertNotNull(AttendanceRecord::first()->check_out);
        $this->actingAs($a->user)->get(route('pegawai.attendance.index'))->assertOk();
    }

    public function test_check_in_requires_a_schedule_today(): void
    {
        $a = $this->emp('A');
        $s = $this->sched($a, today()->addDay()->toDateString());
        $this->actingAs($a->user)->post(route('pegawai.attendance.check-in', $s))->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_admin_correction_requires_reason_and_is_logged(): void
    {
        $a = $this->emp('A');
        $s = $this->sched($a, today()->toDateString());
        $rec = AttendanceRecord::create(['employee_id' => $a->id, 'schedule_id' => $s->id, 'work_date' => today()->toDateString(), 'status' => 'terlambat', 'check_in' => '09:00']);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.attendance.update', $rec), ['status' => 'hadir', 'check_in' => '08:00'])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->put(route('admin.attendance.update', $rec), ['status' => 'hadir', 'check_in' => '08:00', 'reason' => 'Salah input'])->assertSessionHasNoErrors();

        $rec->refresh();
        $this->assertSame('hadir', $rec->status);
        $this->assertSame($admin->id, $rec->corrected_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'attendance.corrected']);
        $this->actingAs($admin)->get(route('admin.attendance.index', ['from' => today()->toDateString()]))->assertOk()->assertSee('Salah input');
    }

    public function test_admin_manual_attendance_prevents_duplicates(): void
    {
        $a = $this->emp('A');
        $admin = $this->admin();
        $data = ['employee_id' => $a->id, 'work_date' => today()->toDateString(), 'status' => 'izin', 'reason' => 'Surat izin'];
        $this->actingAs($admin)->post(route('admin.attendance.store'), $data)->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.attendance.store'), $data)->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_leave_request_flow_creates_attendance_without_duplicates(): void
    {
        $a = $this->emp('A');
        $d1 = today()->addDay();
        $d2 = today()->addDays(2);
        $this->sched($a, $d1->toDateString());
        $this->sched($a, $d2->toDateString());
        $payload = ['type' => 'cuti', 'start_date' => $d1->toDateString(), 'end_date' => $d2->toDateString(), 'reason' => 'Keluarga'];

        $this->actingAs($a->user)->post(route('pegawai.requests.store', 'leave'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($a->user)->post(route('pegawai.requests.store', 'leave'), $payload)->assertSessionHas('error');
        $this->assertDatabaseCount('leave_requests', 1);
        $this->assertDatabaseCount('attendance_records', 0);

        $l = LeaveRequest::first();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.approvals.approve', ['leave', $l->id]))->assertSessionHas('success');
        $this->assertSame(2, AttendanceRecord::where('status', 'cuti')->count());

        $this->actingAs($admin)->patch(route('admin.approvals.approve', ['leave', $l->id]))->assertSessionHas('error');
        $this->assertSame(2, AttendanceRecord::count());
        $this->assertCount(2, $a->user->notifications);
    }

    public function test_leave_reject_requires_note_and_end_before_start_invalid(): void
    {
        $a = $this->emp('A');
        $this->actingAs($a->user)->post(route('pegawai.requests.store', 'leave'), ['type' => 'izin', 'start_date' => '2026-12-10', 'end_date' => '2026-12-01', 'reason' => 'x'])
            ->assertSessionHasErrors('end_date');

        LeaveRequest::create(['employee_id' => $a->id, 'type' => 'izin', 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'reason' => 'x', 'status' => 'menunggu']);
        $l = LeaveRequest::first();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.approvals.reject', ['leave', $l->id]), [])->assertSessionHasErrors('decision_note');
        $this->actingAs($admin)->patch(route('admin.approvals.reject', ['leave', $l->id]), ['decision_note' => 'Tidak sesuai'])->assertSessionHas('success');
        $this->assertSame('ditolak', $l->fresh()->status);
    }

    public function test_overtime_flow(): void
    {
        $a = $this->emp('A');
        $this->actingAs($a->user)->post(route('pegawai.requests.store', 'overtime'), ['work_date' => today()->toDateString(), 'start_time' => '16:00', 'end_time' => '18:00', 'reason' => 'Laporan'])
            ->assertSessionHasNoErrors();
        $this->actingAs($a->user)->post(route('pegawai.requests.store', 'overtime'), ['work_date' => today()->toDateString(), 'start_time' => '17:00', 'end_time' => '19:00', 'reason' => 'Laporan'])
            ->assertSessionHas('error');
        $o = OvertimeRequest::first();
        $this->actingAs($this->admin())->patch(route('admin.approvals.approve', ['overtime', $o->id]))->assertSessionHas('success');
        $this->assertSame('disetujui', $o->fresh()->status);
    }

    public function test_pegawai_cannot_cancel_another_users_leave(): void
    {
        $a = $this->emp('A');
        $b = $this->emp('B');
        $l = LeaveRequest::create(['employee_id' => $a->id, 'type' => 'izin', 'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'reason' => 'x', 'status' => 'menunggu']);
        $this->actingAs($b->user)->patch(route('pegawai.requests.cancel', ['leave', $l->id]))->assertForbidden();
    }

    private function seedReportData(): array
    {
        $a = $this->emp('Siti Laporan');
        $a->update(['department_id' => null, 'nip' => 'A-1']);
        $b = $this->emp('Budi Lain');
        $this->sched($b, '2026-01-05');
        foreach (['2026-01-05' => 'hadir', '2026-01-06' => 'terlambat', '2026-01-07' => 'izin', '2026-01-08' => null, '2026-01-09' => null] as $date => $status) {
            $s = $this->sched($a, $date);
            if ($status) {
                AttendanceRecord::create(['employee_id' => $a->id, 'schedule_id' => $s->id, 'work_date' => $date, 'status' => $status, 'check_in' => '08:00']);
            }
        }
        Holiday::create(['date' => '2026-01-09', 'name' => 'Libur contoh']);
        OvertimeRequest::create(['employee_id' => $a->id, 'work_date' => '2026-01-10', 'start_time' => '08:00', 'end_time' => '10:00', 'reason' => 'x', 'status' => 'disetujui']);
        return [$a, $b];
    }

    public function test_monthly_report_calculation(): void
    {
        [$a] = $this->seedReportData();
        $report = ReportService::monthly(1, 2026);
        $row = $report['rows']->firstWhere('id', $a->id);

        $this->assertSame(4, $row['workdays']);      // hari libur tidak dihitung
        $this->assertSame(4, $row['scheduled']);
        $this->assertSame(2, $row['present']);       // hadir + terlambat
        $this->assertSame(1, $row['late']);
        $this->assertSame(1, $row['izin']);
        $this->assertSame(1, $row['absent']);        // 08 Jan tanpa catatan
        $this->assertEquals(2.0, $row['overtime_hours']);
        $this->assertSame(2, $report['activeCount']);
    }

    public function test_report_filters_and_pegawai_privacy(): void
    {
        [$a] = $this->seedReportData();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.reports.index', ['month' => 1, 'year' => 2026]))->assertOk()->assertSee('Siti Laporan')->assertSee('Budi Lain');
        $this->actingAs($admin)->get(route('admin.reports.index', ['month' => 1, 'year' => 2026, 'q' => 'Siti']))->assertOk()->assertSee('Siti Laporan')->assertDontSee('Budi Lain');

        $this->actingAs($a->user)->get(route('pegawai.report.index', ['month' => 1, 'year' => 2026]))->assertOk()->assertSee('Siti Laporan')->assertDontSee('Budi Lain');
        $this->actingAs($a->user)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_pdf_and_excel_exports_are_real_files(): void
    {
        $this->seedReportData();
        $admin = $this->admin();

        $pdf = $this->actingAs($admin)->get(route('admin.reports.pdf', ['month' => 1, 'year' => 2026]));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $xlsx = $this->actingAs($admin)->get(route('admin.reports.excel', ['month' => 1, 'year' => 2026]));
        $xlsx->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());

        $this->actingAs($admin)->get(route('admin.reports.print', ['month' => 1, 'year' => 2026]))->assertOk()->assertSee('window.print');
    }

    public function test_report_page_renders_empty_state(): void
    {
        $this->actingAs($this->admin())->get(route('admin.reports.index', ['month' => 3, 'year' => 2020]))->assertOk()->assertSee('Tidak ada data');
    }

    public function test_announcement_creates_notifications_and_pegawai_can_read(): void
    {
        $a = $this->emp('A');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.announcements.store'), ['title' => 'Rapat Bulanan', 'body' => 'Jumat pukul 10.00'])->assertSessionHasNoErrors();
        $this->assertCount(1, $a->user->notifications);
        $this->actingAs($a->user)->get(route('pegawai.announcements.index'))->assertOk()->assertSee('Rapat Bulanan');
        $this->actingAs($a->user)->get(route('notifications.index'))->assertOk()->assertSee('Pengumuman baru');
        $this->actingAs($a->user)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $a->user->unreadNotifications()->count());
    }

    public function test_settings_and_holiday_and_activity_log_pages(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.settings.update'), ['institution_name' => 'Puskesmas Seroja', 'late_tolerance_minutes' => 10])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.settings.holidays.store'), ['date' => '2026-12-25', 'name' => 'Natal'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.settings.holidays.store'), ['date' => '2026-12-25', 'name' => 'Natal'])->assertSessionHasErrors('date');
        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Natal');
        $this->actingAs($admin)->get(route('admin.activity-logs.index'))->assertOk()->assertSee('Mengubah pengaturan');
    }

    public function test_shift_management_and_profile_update(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.shifts.store'), ['name' => 'Piket Malam', 'type' => 'piket', 'start_time' => '22:00', 'end_time' => '06:00', 'crosses_midnight' => 1, 'color' => '#336699'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.shifts.store'), ['name' => 'Salah', 'type' => 'piket', 'start_time' => '12:00', 'end_time' => '08:00'])->assertSessionHasErrors('end_time');
        $this->actingAs($admin)->get(route('admin.shifts.index'))->assertOk()->assertSee('Piket Malam');

        $a = $this->emp('A');
        $this->actingAs($a->user)->put(route('pegawai.profile.update'), ['phone' => '0812', 'address' => 'Jl. Contoh'])->assertSessionHasNoErrors();
        $this->assertSame('0812', $a->fresh()->phone);
        $this->actingAs($a->user)->get(route('pegawai.profile.edit'))->assertOk();
    }
}
