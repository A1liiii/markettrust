<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile information.
     */
    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information, avatar, or password.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        // 1. Perbarui Username jika diubah dan valid
        if ($request->filled('username') && $request->input('username') !== $user->username) {
            $user->username = $request->input('username');
        }

        // 2. Upload Avatar baru jika disertakan
        if ($request->hasFile('avatar')) {
            $oldRawAvatar = $user->getRawOriginal('avatar_url');
            if ($oldRawAvatar && ! str_starts_with($oldRawAvatar, 'http')) {
                $oldPath = str_replace('/storage/', '', $oldRawAvatar);
                Storage::disk('public')->delete($oldPath);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = Storage::url($avatarPath);
        }

        // 3. Perbarui Password jika diisi
        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->input('password'));
        }

        $user->save();

        return redirect()->route('profile.show')
            ->with('success', 'Profil Anda berhasil diperbarui!');
    }
}
