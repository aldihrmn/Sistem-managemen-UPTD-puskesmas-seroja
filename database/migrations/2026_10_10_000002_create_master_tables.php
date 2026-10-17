<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('positions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $t->string('nip')->nullable()->unique();
            $t->string('full_name');
            $t->enum('gender', ['L', 'P'])->nullable();
            $t->string('birth_place')->nullable();
            $t->date('birth_date')->nullable();
            $t->text('address')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable()->unique();
            $t->string('education')->nullable();
            $t->string('profession')->nullable();
            $t->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employment_status')->nullable();
            $t->date('start_date')->nullable();
            $t->string('photo')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['department_id', 'is_active']);
        });

        Schema::create('shifts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->enum('type', ['reguler', 'pelayanan', 'piket', 'shift', 'rapat', 'kegiatan'])->default('reguler');
            $t->time('start_time');
            $t->time('end_time');
            $t->boolean('crosses_midnight')->default(false);
            $t->string('color', 20)->default('#16a34a');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('holidays', function (Blueprint $t) {
            $t->id();
            $t->date('date')->unique();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'holidays', 'shifts', 'employees', 'positions', 'departments'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
