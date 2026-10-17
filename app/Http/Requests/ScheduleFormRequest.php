<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $base = [
            'work_date' => ['required', 'date'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'crosses_midnight' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        return $this->route('schedule')
            ? $base + ['employee_id' => ['required', 'exists:employees,id']]
            : $base + ['employee_ids' => ['required', 'array', 'min:1'], 'employee_ids.*' => ['exists:employees,id']];
    }

    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Pilih minimal satu pegawai.',
            'work_date.required' => 'Tanggal jadwal wajib diisi.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
            'start_time.date_format' => 'Format jam mulai tidak valid.',
            'end_time.date_format' => 'Format jam selesai tidak valid.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! $v->errors()->hasAny(['start_time', 'end_time']) && ! $this->boolean('crosses_midnight')
                && $this->end_time <= $this->start_time) {
                $v->errors()->add('end_time', 'Jam selesai harus lebih akhir dari jam mulai (centang "lintas tengah malam" bila berakhir keesokan hari).');
            }
        });
    }
}
