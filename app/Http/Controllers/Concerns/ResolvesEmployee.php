<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;

trait ResolvesEmployee
{
    protected function me(): Employee
    {
        return auth()->user()->employee ?? abort(403, 'Akun Anda belum dihubungkan dengan data pegawai.');
    }
}
