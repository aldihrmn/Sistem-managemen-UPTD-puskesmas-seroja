<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $emp = $this->route('employee');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'nip')->ignore($emp?->id)],
            'gender' => ['nullable', 'in:L,P'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                Rule::requiredIf(fn () => $this->boolean('create_account')),
                'nullable', 'email', 'max:255',
                Rule::unique('employees', 'email')->ignore($emp?->id),
                Rule::unique('users', 'email')->ignore($emp?->user_id),
            ],
            'education' => ['nullable', 'string', 'max:100'],
            'profession' => ['nullable', 'string', 'max:100'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employment_status' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
            'create_account' => ['nullable', 'boolean'],
            'password' => [Rule::requiredIf(fn () => $this->boolean('create_account')), 'nullable', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'nip.unique' => 'NIP/identitas sudah digunakan pegawai lain.',
            'email.unique' => 'Email sudah digunakan.',
            'email.required' => 'Email wajib diisi jika membuat akun login.',
            'password.required' => 'Password wajib diisi jika membuat akun login.',
            'password.min' => 'Password minimal 8 karakter.',
            'photo.image' => 'Foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}
