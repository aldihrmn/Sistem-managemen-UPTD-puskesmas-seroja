<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleRequest extends Model
{
    protected $guarded = [];
    protected $casts = [
        'requested_date' => 'date:Y-m-d',
        'processed_at' => 'datetime',
        'before_snapshot' => 'array',
        'after_snapshot' => 'array',
    ];

    public const TYPES = ['ubah' => 'Perubahan jadwal', 'minta' => 'Permintaan jadwal', 'tukar' => 'Pertukaran jadwal', 'batal' => 'Pembatalan jadwal'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function targetEmployee() { return $this->belongsTo(Employee::class, 'target_employee_id'); }
    public function schedule() { return $this->belongsTo(EmployeeSchedule::class, 'schedule_id'); }
    public function targetSchedule() { return $this->belongsTo(EmployeeSchedule::class, 'target_schedule_id'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}
