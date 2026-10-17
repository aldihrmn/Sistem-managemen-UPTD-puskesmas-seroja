<?php

namespace Tests\Feature;

use App\Models\ScheduleRequest;

class ScheduleRequestFlowTest extends SchedulingBase
{
    private function ubahPayload($schedule, array $o = []): array
    {
        return array_merge(['type' => 'ubah', 'schedule_id' => $schedule->id, 'requested_date' => today()->addDays(3)->toDateString(),
            'requested_start' => '13:00', 'requested_end' => '15:00', 'reason' => 'Ada keperluan keluarga'], $o);
    }

    private function setupRequest(): array
    {
        $a = $this->emp('A');
        $s = $this->sched($a, today()->addDays(2)->toDateString());
        $this->actingAs($a->user)->post(route('pegawai.schedule-requests.store'), $this->ubahPayload($s))->assertSessionHasNoErrors();
        return [$a, $s, ScheduleRequest::first()];
    }

    public function test_pending_request_does_not_change_official_schedule(): void
    {
        [, $s, $r] = $this->setupRequest();
        $this->assertSame('menunggu', $r->status);
        $this->assertSame('08:00', substr($s->fresh()->start_time, 0, 5));
        $this->assertSame($s->work_date->toDateString(), $s->fresh()->work_date->toDateString());
    }

    public function test_approval_changes_schedule_records_snapshot_and_notifies(): void
    {
        [$a, $s, $r] = $this->setupRequest();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.schedule-requests.approve', $r), ['decision_note' => 'OK'])->assertSessionHas('success');

        $r->refresh();
        $this->assertSame('disetujui', $r->status);
        $this->assertSame($admin->id, $r->processed_by);
        $this->assertNotNull($r->processed_at);
        $this->assertSame('08:00', $r->before_snapshot['start_time']);
        $this->assertSame('13:00', $r->after_snapshot['start_time']);
        $this->assertSame('13:00', substr($s->fresh()->start_time, 0, 5));
        $this->assertSame(today()->addDays(3)->toDateString(), $s->fresh()->work_date->toDateString());
        $this->assertGreaterThanOrEqual(2, $a->user->notifications()->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'schedule_request.approved']);
    }

    public function test_rejection_requires_note_and_keeps_schedule(): void
    {
        [, $s, $r] = $this->setupRequest();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.schedule-requests.reject', $r), [])->assertSessionHasErrors('decision_note');
        $this->actingAs($admin)->patch(route('admin.schedule-requests.reject', $r), ['decision_note' => 'Kekurangan tenaga'])->assertSessionHas('success');
        $this->assertSame('ditolak', $r->fresh()->status);
        $this->assertSame('08:00', substr($s->fresh()->start_time, 0, 5));
    }

    public function test_double_approval_is_blocked(): void
    {
        [, , $r] = $this->setupRequest();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('admin.schedule-requests.approve', $r))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.schedule-requests.approve', $r))->assertSessionHas('error');
        $this->actingAs($admin)->patch(route('admin.schedule-requests.reject', $r), ['decision_note' => 'x'])->assertSessionHas('error');
        $this->assertSame('disetujui', $r->fresh()->status);
    }

    public function test_approval_blocked_when_conflict_exists(): void
    {
        [$a, $s, $r] = $this->setupRequest();
        $this->sched($a, today()->addDays(3)->toDateString(), '14:00', '16:00');
        $this->actingAs($this->admin())->patch(route('admin.schedule-requests.approve', $r))->assertSessionHas('error');
        $this->assertSame('menunggu', $r->fresh()->status);
        $this->assertSame('08:00', substr($s->fresh()->start_time, 0, 5));
    }

    public function test_admin_detail_page_shows_conflict_preview(): void
    {
        [$a, , $r] = $this->setupRequest();
        $this->sched($a, today()->addDays(3)->toDateString(), '14:00', '16:00');
        $this->actingAs($this->admin())->get(route('admin.schedule-requests.show', $r))->assertOk()->assertSee('Benturan jadwal ditemukan');
    }

    public function test_swap_exchanges_owners_on_approval(): void
    {
        $a = $this->emp('A');
        $b = $this->emp('B');
        $s1 = $this->sched($a, today()->addDays(2)->toDateString());
        $s2 = $this->sched($b, today()->addDays(4)->toDateString());
        $this->actingAs($a->user)->post(route('pegawai.schedule-requests.store'), ['type' => 'tukar', 'schedule_id' => $s1->id, 'target_schedule_id' => $s2->id, 'reason' => 'Tukar shift'])
            ->assertSessionHasNoErrors();
        $r = ScheduleRequest::first();
        $this->actingAs($this->admin())->patch(route('admin.schedule-requests.approve', $r))->assertSessionHas('success');
        $this->assertSame($b->id, $s1->fresh()->employee_id);
        $this->assertSame($a->id, $s2->fresh()->employee_id);
    }

    public function test_cancel_request_approval_cancels_schedule(): void
    {
        $a = $this->emp('A');
        $s = $this->sched($a, today()->addDays(2)->toDateString());
        $this->actingAs($a->user)->post(route('pegawai.schedule-requests.store'), ['type' => 'batal', 'schedule_id' => $s->id, 'reason' => 'Sakit'])->assertSessionHasNoErrors();
        $this->actingAs($this->admin())->patch(route('admin.schedule-requests.approve', ScheduleRequest::first()))->assertSessionHas('success');
        $this->assertSame('dibatalkan', $s->fresh()->status);
    }

    public function test_request_for_new_schedule_creates_it_only_after_approval(): void
    {
        $a = $this->emp('A');
        $d = today()->addDays(5)->toDateString();
        $this->actingAs($a->user)->post(route('pegawai.schedule-requests.store'), ['type' => 'minta', 'requested_date' => $d, 'requested_start' => '09:00', 'requested_end' => '11:00', 'reason' => 'Ingin tugas'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employee_schedules', 0);
        $this->actingAs($this->admin())->patch(route('admin.schedule-requests.approve', ScheduleRequest::first()))->assertSessionHas('success');
        $this->assertDatabaseCount('employee_schedules', 1);
    }

    public function test_pegawai_cannot_request_with_someone_elses_schedule(): void
    {
        $a = $this->emp('A');
        $b = $this->emp('B');
        $s = $this->sched($a, today()->addDays(2)->toDateString());
        $this->actingAs($b->user)->post(route('pegawai.schedule-requests.store'), $this->ubahPayload($s))->assertSessionHasErrors('schedule_id');
        $this->assertDatabaseCount('schedule_requests', 0);
    }

    public function test_pegawai_cannot_cancel_other_users_request_but_can_cancel_own_pending(): void
    {
        [$a, , $r] = $this->setupRequest();
        $b = $this->emp('B');
        $this->actingAs($b->user)->patch(route('pegawai.schedule-requests.cancel', $r))->assertForbidden();
        $this->actingAs($a->user)->patch(route('pegawai.schedule-requests.cancel', $r))->assertSessionHas('success');
        $this->assertSame('dibatalkan', $r->fresh()->status);
    }

    public function test_pegawai_cannot_approve(): void
    {
        [$a, , $r] = $this->setupRequest();
        $this->actingAs($a->user)->patch(route('admin.schedule-requests.approve', $r))->assertForbidden();
    }

    public function test_duplicate_pending_request_for_same_schedule_is_blocked(): void
    {
        [$a, $s] = $this->setupRequest();
        $this->actingAs($a->user)->post(route('pegawai.schedule-requests.store'), $this->ubahPayload($s, ['requested_start' => '14:00', 'requested_end' => '16:00']))->assertSessionHas('error');
        $this->assertDatabaseCount('schedule_requests', 1);
    }
}
