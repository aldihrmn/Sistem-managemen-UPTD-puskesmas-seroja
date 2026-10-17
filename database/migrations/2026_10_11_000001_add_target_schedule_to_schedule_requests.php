<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schedule_requests', function (Blueprint $t) {
            $t->foreignId('target_schedule_id')->nullable()->after('target_employee_id')
                ->constrained('employee_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('target_schedule_id');
        });
    }
};
