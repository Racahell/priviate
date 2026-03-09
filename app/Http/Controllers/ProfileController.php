<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        if ($user && $user->hasRole('siswa') && empty($user->code)) {
            $user->forceFill(['code' => $this->generateStudentCode()])->save();
        }

        return view('profile.edit', [
            'user' => $user,
            'supportsLocationNotes' => Schema::hasColumn('users', 'location_notes'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (Schema::hasColumn('users', 'location_notes')) {
            $validated['location_notes'] = $request->validate([
                'location_notes' => 'nullable|string|max:1000',
            ])['location_notes'] ?? null;
        }

        if (!is_null($validated['latitude'] ?? null) || !is_null($validated['longitude'] ?? null)) {
            if (!is_numeric($validated['latitude'] ?? null) || !is_numeric($validated['longitude'] ?? null)) {
                return back()->withErrors(['address' => 'Koordinat lokasi tidak valid.'])->withInput();
            }

            $validated['latitude'] = round((float) $validated['latitude'], 8);
            $validated['longitude'] = round((float) $validated['longitude'], 8);

            if ($validated['latitude'] < -90 || $validated['latitude'] > 90 || $validated['longitude'] < -180 || $validated['longitude'] > 180) {
                return back()->withErrors(['address' => 'Koordinat lokasi berada di luar batas yang valid.'])->withInput();
            }
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = 'storage/' . $path;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    private function generateStudentCode(): string
    {
        do {
            $candidate = 'SIS-' . strtoupper(Str::random(8));
            $exists = \App\Models\User::where('code', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }
}
