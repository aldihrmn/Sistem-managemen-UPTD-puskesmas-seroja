<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', ['announcements' => Announcement::with('department')->latest()->paginate(10)]);
    }

    public function create()
    {
        return view('admin.announcements.create', ['departments' => Department::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $d = $this->validated($request);
        $d['created_by'] = $request->user()->id;
        $d['published_at'] = $d['published_at'] ?? now();
        $a = Announcement::create($d);

        if ($a->published_at->lte(now())) {
            $users = User::where('role', 'pegawai')->where('is_active', true)
                ->when($a->department_id, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $a->department_id)))->get();
            Notifier::toUsers($users, 'Pengumuman baru', $a->title, route('pegawai.announcements.index'));
        }
        ActivityLogger::log('announcement.created', "Membuat pengumuman {$a->title}", $a);

        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman dipublikasikan.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', ['announcement' => $announcement, 'departments' => Department::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $d = $this->validated($request);
        $d['published_at'] = $d['published_at'] ?? $announcement->published_at;
        $announcement->update($d);
        ActivityLogger::log('announcement.updated', "Mengubah pengumuman {$announcement->title}", $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman diperbarui.');
    }

    public function destroy(Announcement $announcement)
    {
        ActivityLogger::log('announcement.deleted', "Menghapus pengumuman {$announcement->title}", $announcement);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Pengumuman dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'published_at' => ['nullable', 'date'],
        ], ['title.required' => 'Judul wajib diisi.', 'body.required' => 'Isi pengumuman wajib diisi.']);
    }
}
