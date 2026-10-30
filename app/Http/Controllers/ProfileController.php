<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Conversation;
use App\Models\KeepObligation;
use App\Models\TransactionCancellation;
use App\Models\User;
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
    public function show(Request $request, ?User $user = null): View
    {
        $targetUser = $user;

        if (! $targetUser && $request->filled('user')) {
            $targetUser = User::find($request->input('user'));
            if (! $targetUser) {
                abort(404);
            }
        }

        if (! $targetUser && $request->filled('username')) {
            $targetUser = User::where('username', $request->input('username'))->first();
            if (! $targetUser) {
                abort(404);
            }
        }

        if (! $targetUser) {
            $targetUser = $request->user();
        }

        if (! $targetUser) {
            abort(404);
        }

        $isOwnProfile = (int) $request->user()?->user_id === (int) $targetUser->user_id;

        if ($isOwnProfile) {
            $targetUser->loadMissing('activePayoutAccount');
        }

        $reputationEvents = $targetUser->reputationEvents()
            ->with(['actor', 'transaction'])
            ->latest('reputation_event_id')
            ->paginate(10)
            ->withQueryString();

        $sellerCancellationsCount = TransactionCancellation::query()
            ->whereHas('transaction.agreement', function ($query) use ($targetUser) {
                $query->where('seller_id', $targetUser->user_id);
            })
            ->where('status', TransactionCancellation::STATUS_APPROVED)
            ->where('responsible_party', TransactionCancellation::PARTY_SELLER)
            ->count();

        $buyerCancellationsCount = TransactionCancellation::query()
            ->whereHas('transaction.agreement', function ($query) use ($targetUser) {
                $query->where('buyer_id', $targetUser->user_id);
            })
            ->where('status', TransactionCancellation::STATUS_APPROVED)
            ->where('responsible_party', TransactionCancellation::PARTY_BUYER)
            ->count();

        $openKeepCount = $targetUser->keepObligations()
            ->where('status', KeepObligation::STATUS_OPEN)
            ->count();

        $settledKeepCount = $targetUser->keepObligations()
            ->where('status', KeepObligation::STATUS_SETTLED)
            ->count();

        $openKeepAmount = (float) $targetUser->keepObligations()
            ->where('status', KeepObligation::STATUS_OPEN)
            ->sum('remaining_amount');

        $existingConversation = (! $isOwnProfile && $request->user())
            ? Conversation::query()
                ->where(function ($query) use ($request, $targetUser) {
                    $query->where('participant_one_id', $request->user()->user_id)
                        ->where('participant_two_id', $targetUser->user_id);
                })
                ->orWhere(function ($query) use ($request, $targetUser) {
                    $query->where('participant_one_id', $targetUser->user_id)
                        ->where('participant_two_id', $request->user()->user_id);
                })
                ->first()
            : null;

        return view('profile.show', [
            'user' => $targetUser,
            'isOwnProfile' => $isOwnProfile,
            'reputationEvents' => $reputationEvents,
            'sellerCancellationsCount' => $sellerCancellationsCount,
            'buyerCancellationsCount' => $buyerCancellationsCount,
            'openKeepCount' => $openKeepCount,
            'settledKeepCount' => $settledKeepCount,
            'openKeepAmount' => $openKeepAmount,
            'existingConversation' => $existingConversation,
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
