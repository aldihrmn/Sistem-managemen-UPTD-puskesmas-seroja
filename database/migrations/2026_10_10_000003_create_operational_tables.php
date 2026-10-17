<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $t->date('work_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->boolean('crosses_midnight')->default(false);
            $t->string('location')->nullable();
            $t->text('notes')->nullable();
            $t->enum('status', ['aktif', 'dibatalkan'])->default('aktif');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['employee_id', 'work_date']);
            $t->index('work_date');
        });

        Schema::create('schedule_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->enum('type', ['ubah', 'minta', 'tukar', 'batal']);
            $t->foreignId('schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
            $t->foreignId('target_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->date('requested_date')->nullable();
            $t->time('requested_start')->nullable();
            $t->time('requested_end')->nullable();
            $t->foreignId('requested_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $t->text('reason');
            $t->string('attachment')->nullable();
            $t->enum('status', ['menunggu', 'disetujui', 'ditolak', 'dibatalkan'])->default('menunggu');
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('processed_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->json('before_snapshot')->nullable();
            $t->json('after_snapshot')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });

        Schema::create('attendance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('schedule_id')->nullable()->constrained('employee_schedules')->nullOnDelete();
            $t->date('work_date');
            $t->time('check_in')->nullable();
            $t->time('check_out')->nullable();
            $t->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'alpa'])->default('hadir');
            $t->text('notes')->nullable();
            $t->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('correction_reason')->nullable();
            $t->timestamps();
            $t->unique(['employee_id', 'schedule_id']);
            $t->index(['employee_id', 'work_date']);
        });

        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->enum('type', ['cuti', 'izin', 'sakit']);
            $t->date('start_date');
            $t->date('end_date');
            $t->text('reason');
            $t->string('attachment')->nullable();
            $t->enum('status', ['menunggu', 'disetujui', 'ditolak', 'dibatalkan'])->default('menunggu');
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('processed_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'start_date']);
        });

        Schema::create('overtime_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->date('work_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->text('reason');
            $t->enum('status', ['menunggu', 'disetujui', 'ditolak', 'dibatalkan'])->default('menunggu');
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('processed_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('description');
            $t->json('properties')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'notifications', 'announcements', 'overtime_requests', 'leave_requests', 'attendance_records', 'schedule_requests', 'employee_schedules'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
