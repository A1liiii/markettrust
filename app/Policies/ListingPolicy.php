<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar listing miliknya.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Tentukan apakah pengguna dapat melihat detail listing tertentu.
     */
    public function view(User $user, Listing $listing): bool
    {
        return $user->user_id === $listing->seller_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat listing baru.
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui listing.
     */
    public function update(User $user, Listing $listing): bool
    {
        return $user->user_id === $listing->seller_id && $user->isActive();
    }

    /**
     * Tentukan apakah pengguna dapat menghapus atau menutup listing.
     */
    public function delete(User $user, Listing $listing): bool
    {
        return $user->user_id === $listing->seller_id && $user->isActive();
    }
}
