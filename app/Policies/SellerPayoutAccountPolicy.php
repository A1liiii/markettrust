<?php

namespace App\Policies;

use App\Models\SellerPayoutAccount;
use App\Models\User;

class SellerPayoutAccountPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar rekening payout miliknya.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail rekening payout tertentu.
     * Hanya pemilik akun atau administrator yang berhak melihat.
     */
    public function view(User $user, SellerPayoutAccount $account): bool
    {
        return $user->isActive() && ($user->user_id === $account->seller_id || $user->isAdmin());
    }

    /**
     * Tentukan apakah pengguna dapat menambahkan rekening payout baru.
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui rekening payout miliknya.
     */
    public function update(User $user, SellerPayoutAccount $account): bool
    {
        return $user->isActive() && $user->user_id === $account->seller_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus rekening payout miliknya.
     */
    public function delete(User $user, SellerPayoutAccount $account): bool
    {
        return $user->isActive() && $user->user_id === $account->seller_id;
    }

    /**
     * Tentukan apakah pengguna dapat mengaktifkan rekening payout miliknya sebagai tujuan utama.
     */
    public function activate(User $user, SellerPayoutAccount $account): bool
    {
        return $user->isActive() && $user->user_id === $account->seller_id;
    }
}
