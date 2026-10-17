<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        $dept = auth()->user()->employee?->department_id;

        return view('pegawai.announcements.index', ['announcements' => Announcement::whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('department_id')->when($dept, fn ($w) => $w->orWhere('department_id', $dept)))
            ->latest('published_at')->paginate(10)]);
    }
}
