<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $guarded = [];
    protected $casts = ['work_date' => 'date:Y-m-d'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function schedule() { return $this->belongsTo(EmployeeSchedule::class, 'schedule_id'); }
    public function corrector() { return $this->belongsTo(User::class, 'corrected_by'); }
}
