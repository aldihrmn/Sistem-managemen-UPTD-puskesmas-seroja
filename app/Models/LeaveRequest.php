<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'processed_at' => 'datetime'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}
