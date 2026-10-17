<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSchedule extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['work_date' => 'date:Y-m-d', 'crosses_midnight' => 'boolean'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function attendance() { return $this->hasOne(AttendanceRecord::class, 'schedule_id'); }
}
