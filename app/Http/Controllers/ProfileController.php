<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        if ($user && $user->hasRole('siswa') && empty($user->code)) {
            $user->forceFill(['code' => $this->generateStudentCode()])->save();
        }

        return view('profile.edit', ['user' => $user]);
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
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

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
