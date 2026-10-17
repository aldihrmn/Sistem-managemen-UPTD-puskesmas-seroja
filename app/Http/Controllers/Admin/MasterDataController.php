<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD dinamis Unit Kerja (department) dan Jabatan (position). */
class MasterDataController extends Controller
{
    private function config(string $type): array
    {
        return match ($type) {
            'department' => [Department::class, 'Unit Kerja', true],
            'position' => [Position::class, 'Jabatan', false],
            default => abort(404),
        };
    }

    public function index(string $type)
    {
        [$model, $label, $hasDescription] = $this->config($type);

        return view('admin.master.index', [
            'type' => $type, 'label' => $label, 'hasDescription' => $hasDescription,
            'items' => $model::withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        [$model, $label, $hasDescription] = $this->config($type);
        $table = (new $model)->getTable();

        $data = $request->validate($this->rules($table, $hasDescription), ['name.unique' => "$label dengan nama tersebut sudah ada."]);
        $item = $model::create($data);
        ActivityLogger::log("$type.created", "Menambah $label {$item->name}", $item);

        return back()->with('success', "$label berhasil ditambahkan.");
    }

    public function update(Request $request, string $type, int $id)
    {
        [$model, $label, $hasDescription] = $this->config($type);
        $item = $model::findOrFail($id);

        $data = $request->validate($this->rules((new $model)->getTable(), $hasDescription, $id), ['name.unique' => "$label dengan nama tersebut sudah ada."]);
        $item->update($data);
        ActivityLogger::log("$type.updated", "Mengubah $label {$item->name}", $item);

        return back()->with('success', "$label berhasil diperbarui.");
    }

    public function toggle(string $type, int $id)
    {
        [$model, $label] = $this->config($type);
        $item = $model::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
        ActivityLogger::log("$type.toggled", ($item->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." $label {$item->name}", $item);

        return back()->with('success', "Status $label diperbarui.");
    }

    private function rules(string $table, bool $hasDescription, ?int $ignore = null): array
    {
        $rules = ['name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($ignore)]];
        if ($hasDescription) {
            $rules['description'] = ['nullable', 'string', 'max:500'];
        }
        return $rules;
    }
}
