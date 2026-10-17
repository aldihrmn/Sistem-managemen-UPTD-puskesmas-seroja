<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['work_date' => 'date:Y-m-d', 'processed_at' => 'datetime'];

    public function employee() { return $this->belongsTo(Employee::class); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
}
