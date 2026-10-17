<?php

namespace Tests\Feature;

use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\User;

class SchedulingTest extends SchedulingBase
{
    private function payload(array $o = []): array
    {
        return array_merge(['work_date' => today()->addDays(2)->toDateString(), 'start_time' => '08:00', 'end_time' => '12:00'], $o);
    }

    public function test_pegawai_cannot_access_admin_schedules(): void
    {
        $u = User::factory()->create(['role' => 'pegawai', 'is_active' => true]);
        $this->actingAs($u)->get(route('admin.schedules.index'))->assertForbidden();
    }

    public function test_admin_creates_schedule_for_multiple_employees(): void
    {
        $a = $this->emp('A');
        $b = $this->emp('B');
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id, $b->id]]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employee_schedules', 2);
        $this->assertDatabaseHas('activity_logs', ['action' => 'schedule.created']);
        $this->assertCount(1, $a->user->notifications);
    }

    public function test_overlapping_schedule_is_rejected_with_conflict_info(): void
    {
        $a = $this->emp('A');
        $d = today()->addDays(2)->toDateString();
        $this->sched($a, $d);
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id], 'work_date' => $d, 'start_time' => '11:00', 'end_time' => '13:00']))
            ->assertSessionHas('conflicts');
        $this->assertDatabaseCount('employee_schedules', 1);
    }

    public function test_adjacent_schedule_is_allowed(): void
    {
        $a = $this->emp('A');
        $d = today()->addDays(2)->toDateString();
        $this->sched($a, $d);
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id], 'work_date' => $d, 'start_time' => '12:00', 'end_time' => '14:00']))
            ->assertSessionMissing('conflicts');
        $this->assertDatabaseCount('employee_schedules', 2);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $a = $this->emp('A');
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id], 'start_time' => '12:00', 'end_time' => '08:00']))
            ->assertSessionHasErrors('end_time');
    }

    public function test_cross_midnight_is_allowed_and_detects_next_day_overlap(): void
    {
        $a = $this->emp('A');
        $admin = $this->admin();
        $d1 = today()->addDays(2);
        $this->actingAs($admin)->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id], 'work_date' => $d1->toDateString(), 'start_time' => '22:00', 'end_time' => '06:00', 'crosses_midnight' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employee_schedules', 1);

        $this->actingAs($admin)->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id], 'work_date' => $d1->copy()->addDay()->toDateString(), 'start_time' => '05:00', 'end_time' => '07:00']))
            ->assertSessionHas('conflicts');
        $this->assertDatabaseCount('employee_schedules', 1);
    }

    public function test_approved_leave_blocks_scheduling(): void
    {
        $a = $this->emp('A');
        $d = today()->addDays(2)->toDateString();
        LeaveRequest::create(['employee_id' => $a->id, 'type' => 'cuti', 'start_date' => $d, 'end_date' => $d, 'reason' => 'x', 'status' => 'disetujui']);
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id], 'work_date' => $d]))->assertSessionHas('conflicts');
        $this->assertDatabaseCount('employee_schedules', 0);
    }

    public function test_inactive_employee_cannot_be_scheduled(): void
    {
        $a = $this->emp('A');
        $a->update(['is_active' => false]);
        $this->actingAs($this->admin())->post(route('admin.schedules.store'), $this->payload(['employee_ids' => [$a->id]]))->assertSessionHas('conflicts');
    }

    public function test_update_into_conflict_is_rejected(): void
    {
        $a = $this->emp('A');
        $d = today()->addDays(2)->toDateString();
        $s1 = $this->sched($a, $d, '08:00', '10:00');
        $this->sched($a, $d, '10:00', '12:00');
        $this->actingAs($this->admin())->put(route('admin.schedules.update', $s1), $this->payload(['employee_id' => $a->id, 'work_date' => $d, 'start_time' => '09:00', 'end_time' => '11:00']))
            ->assertSessionHas('conflicts');
        $this->assertSame('08:00', substr($s1->fresh()->start_time, 0, 5));
    }

    public function test_cancel_schedule_notifies_employee(): void
    {
        $a = $this->emp('A');
        $s = $this->sched($a, today()->addDays(2)->toDateString());
        $this->actingAs($this->admin())->patch(route('admin.schedules.cancel', $s))->assertRedirect();
        $this->assertSame('dibatalkan', $s->fresh()->status);
        $this->assertCount(1, $a->user->notifications);
    }

    public function test_schedule_views_render(): void
    {
        $a = $this->emp('Siti Jadwal');
        $d = today()->toDateString();
        $this->sched($a, $d);
        $admin = $this->admin();
        foreach (['day', 'week', 'month'] as $v) {
            $this->actingAs($admin)->get(route('admin.schedules.index', ['view' => $v, 'date' => $d]))->assertOk()->assertSee('Siti Jadwal');
        }
        $this->actingAs($admin)->get(route('admin.schedules.index', ['q' => 'tidak-ada']))->assertOk()->assertSee('Belum ada jadwal');
        $this->actingAs($admin)->get(route('admin.schedules.create'))->assertOk();
    }

    public function test_pegawai_sees_own_and_team_schedule_only_in_scope(): void
    {
        $a = $this->emp('Saya');
        $b = $this->emp('Rekan');
        $this->sched($a, today()->toDateString());
        $this->sched($b, today()->toDateString());
        $this->actingAs($a->user)->get(route('pegawai.schedules.index', ['view' => 'day']))->assertOk()->assertDontSee('Rekan');
        $this->actingAs($a->user)->get(route('pegawai.schedules.index', ['scope' => 'history']))->assertOk();
    }
}
