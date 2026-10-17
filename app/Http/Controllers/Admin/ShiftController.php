<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return view('admin.shifts.index', ['shifts' => Shift::orderBy('name')->get(),
            'types' => ['reguler' => 'Jam kerja reguler', 'pelayanan' => 'Jadwal pelayanan', 'piket' => 'Piket', 'shift' => 'Shift', 'rapat' => 'Rapat', 'kegiatan' => 'Kegiatan khusus']]);
    }

    public function store(Request $request)
    {
        $shift = Shift::create($this->validated($request));
        ActivityLogger::log('shift.created', "Menambah jenis jadwal {$shift->name}", $shift);

        return back()->with('success', 'Jenis jadwal ditambahkan.');
    }

    public function update(Request $request, Shift $shift)
    {
        $shift->update($this->validated($request));
        ActivityLogger::log('shift.updated', "Mengubah jenis jadwal {$shift->name}", $shift);

        return back()->with('success', 'Jenis jadwal diperbarui.');
    }

    public function toggle(Shift $shift)
    {
        $shift->update(['is_active' => ! $shift->is_active]);
        ActivityLogger::log('shift.toggled', "Mengubah status jenis jadwal {$shift->name}", $shift);

        return back()->with('success', 'Status jenis jadwal diperbarui.');
    }

    private function validated(Request $request): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:reguler,pelayanan,piket,shift,rapat,kegiatan'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'crosses_midnight' => ['nullable', 'boolean'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $crosses = $request->boolean('crosses_midnight');
        if (! $crosses && $d['end_time'] <= $d['start_time']) {
            back()->withErrors(['end_time' => 'Jam selesai harus lebih akhir dari jam mulai.'])->throwResponse();
        }

        return $d + ['crosses_midnight' => $crosses, 'color' => $d['color'] ?? '#16a34a'];
    }
}
