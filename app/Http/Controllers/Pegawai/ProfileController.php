<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ResolvesEmployee;

    public function edit()
    {
        return view('pegawai.profile.edit', ['employee' => $this->me()->load(['department', 'position'])]);
    }

    public function update(Request $request)
    {
        $me = $this->me();
        $d = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'], 'address' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], ['current_password.current_password' => 'Password saat ini salah.', 'password.min' => 'Password baru minimal 8 karakter.', 'password.confirmed' => 'Konfirmasi password tidak cocok.']);

        $payload = ['phone' => $d['phone'] ?? null, 'address' => $d['address'] ?? null];
        if ($request->hasFile('photo')) {
            if ($me->photo) {
                Storage::disk('public')->delete($me->photo);
            }
            $payload['photo'] = $request->file('photo')->store('photos', 'public');
        }
        $me->update($payload);

        if (! empty($d['password'])) {
            $request->user()->update(['password' => $d['password']]);
            ActivityLogger::log('profile.password_changed', 'Pegawai mengganti password sendiri', $me);
        }

        return back()->with('success', 'Profil diperbarui.');
    }
}
