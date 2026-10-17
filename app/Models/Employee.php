<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Employee extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ["birth_date" => "date", "start_date" => "date", "is_active" => "boolean"];

    public function user() { return $this->belongsTo(User::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function position() { return $this->belongsTo(Position::class); }
    public function schedules() { return $this->hasMany(EmployeeSchedule::class); }
    public function attendanceRecords() { return $this->hasMany(AttendanceRecord::class); }
    public function scheduleRequests() { return $this->hasMany(ScheduleRequest::class); }
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
    public function overtimeRequests() { return $this->hasMany(OvertimeRequest::class); }
}
