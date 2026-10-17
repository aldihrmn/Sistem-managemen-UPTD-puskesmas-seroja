<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $employees = Employee::with(['department', 'position'])
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$q}%")->orWhere('nip', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($request->filled('department_id'), fn ($b) => $b->where('department_id', $request->department_id))
            ->when($request->filled('position_id'), fn ($b) => $b->where('position_id', $request->position_id))
            ->when($request->filled('profession'), fn ($b) => $b->where('profession', $request->profession))
            ->when($request->query('status') === 'aktif', fn ($b) => $b->where('is_active', true))
            ->when($request->query('status') === 'nonaktif', fn ($b) => $b->where('is_active', false))
            ->orderBy('full_name')->paginate(10)->withQueryString();

        return view('admin.employees.index', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'positions' => Position::orderBy('name')->pluck('name', 'id'),
            'professions' => Employee::whereNotNull('profession')->distinct()->orderBy('profession')->pluck('profession'),
        ]);
    }

    public function create()
    {
        return view('admin.employees.create', $this->formData());
    }

    public function store(EmployeeRequest $request)
    {
        $data = $request->validated();
        $employee = null;

        DB::transaction(function () use ($request, $data, &$employee) {
            $payload = $this->payload($request, $data);

            if ($request->boolean('create_account')) {
                $payload['user_id'] = User::create([
                    'name' => $data['full_name'], 'email' => $data['email'], 'password' => $data['password'],
                    'role' => User::ROLE_PEGAWAI, 'is_active' => $payload['is_active'],
                ])->id;
            }

            $employee = Employee::create($payload);
            ActivityLogger::log('employee.created', "Menambah pegawai {$employee->full_name}", $employee);
        });

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function show(Employee $employee)
    {
        return view('admin.employees.show', ['employee' => $employee->load(['department', 'position', 'user'])]);
    }

    public function edit(Employee $employee)
    {
        return view('admin.employees.edit', $this->formData() + ['employee' => $employee->load('user')]);
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $employee) {
            $payload = $this->payload($request, $data, $employee);
            $employee->update($payload);

            if ($employee->user) {
                $employee->user->update([
                    'name' => $data['full_name'],
                    'email' => $data['email'] ?? $employee->user->email,
                    'is_active' => $payload['is_active'],
                ]);
            } elseif ($request->boolean('create_account')) {
                $employee->update(['user_id' => User::create([
                    'name' => $data['full_name'], 'email' => $data['email'], 'password' => $data['password'],
                    'role' => User::ROLE_PEGAWAI, 'is_active' => $payload['is_active'],
                ])->id]);
            }

            $changes = collect($employee->getChanges())->except('updated_at')->all();
            ActivityLogger::log('employee.updated', "Mengubah data pegawai {$employee->full_name}", $employee, $changes);
        });

        return redirect()->route('admin.employees.show', $employee)->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function toggle(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            $employee->update(['is_active' => ! $employee->is_active]);
            $employee->user?->update(['is_active' => $employee->is_active]);
            ActivityLogger::log(
                $employee->is_active ? 'employee.activated' : 'employee.deactivated',
                ($employee->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." pegawai {$employee->full_name}",
                $employee
            );
        });

        return back()->with('success', $employee->is_active ? 'Pegawai diaktifkan.' : 'Pegawai dinonaktifkan.');
    }

    public function resetPassword(Request $request, Employee $employee)
    {
        if (! $employee->user) {
            return back()->with('error', 'Pegawai ini belum memiliki akun login.');
        }

        $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $employee->user->update(['password' => $request->password]);
        ActivityLogger::log('employee.password_reset', "Reset password akun {$employee->full_name}", $employee);

        return back()->with('success', 'Password berhasil diubah.');
    }

    private function formData(): array
    {
        return [
            'departments' => Department::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'positions' => Position::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ];
    }

    private function payload(EmployeeRequest $request, array $data, ?Employee $employee = null): array
    {
        $payload = collect($data)->except(['create_account', 'password', 'photo'])->all();
        $payload['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            if ($employee?->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $payload['photo'] = $request->file('photo')->store('photos', 'public');
        }

        return $payload;
    }
}
