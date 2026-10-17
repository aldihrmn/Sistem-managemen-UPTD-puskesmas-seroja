<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'institution' => Setting::get('institution_name', 'Puskesmas Seroja'),
            'tolerance' => Setting::get('late_tolerance_minutes', 15),
            'holidays' => Holiday::orderByDesc('date')->limit(50)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'institution_name' => ['required', 'string', 'max:255'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
        ]);
        foreach ($d as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => (string) $v]);
        }
        ActivityLogger::log('settings.updated', 'Mengubah pengaturan sistem');

        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function storeHoliday(Request $request)
    {
        $d = $request->validate(['date' => ['required', 'date', 'unique:holidays,date'], 'name' => ['required', 'string', 'max:255']],
            ['date.unique' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.']);
        $h = Holiday::create($d);
        ActivityLogger::log('holiday.created', "Menambah hari libur {$h->name}", $h);

        return back()->with('success', 'Hari libur ditambahkan.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        ActivityLogger::log('holiday.deleted', "Menghapus hari libur {$holiday->name}", $holiday);
        $holiday->delete();

        return back()->with('success', 'Hari libur dihapus.');
    }
}
