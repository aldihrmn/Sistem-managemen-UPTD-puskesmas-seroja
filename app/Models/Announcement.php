<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];
    protected $casts = ["published_at" => "datetime"];

    public function department() { return $this->belongsTo(Department::class); }
    public function creator() { return $this->belongsTo(User::class, "created_by"); }
}
