<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $guarded = [];
    protected $casts = ["crosses_midnight" => "boolean", "is_active" => "boolean"];

    public function schedules() { return $this->hasMany(EmployeeSchedule::class); }
}
