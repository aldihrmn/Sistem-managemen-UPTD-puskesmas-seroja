<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return view('admin.activity-logs.index', ['logs' => ActivityLog::with('user')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('description', 'like', "%{$q}%")->orWhere('action', 'like', "%{$q}%")))
            ->latest()->paginate(20)->withQueryString()]);
    }
}
