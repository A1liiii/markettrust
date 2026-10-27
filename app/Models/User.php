<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    protected $authPasswordName = 'password_hash';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'google_id',
        'avatar_url',
        'email_verified_at',
        'account_type',
        'account_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->account_type === 'ADMIN';
    }

    public function isActive(): bool
    {
        return $this->account_status === 'ACTIVE';
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password_hash);
    }

    /**
     * Accessor untuk avatar_url agar selalu mengembalikan URL yang valid (Google URL atau asset lokal).
     */
    public function getAvatarUrlAttribute(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return asset(ltrim($value, '/'));
    }

    /**
     * Hitung total poin reputasi dari riwayat event.
     */
    public function getReputationPointsAttribute(): int
    {
        return (int) $this->reputationEvents()->sum('points_delta');
    }

    /**
     * Dapatkan status reputasi legitimasi akun.
     * Hanya bernilai LEGIT atau PROBLEMATIC jika terdapat penetapan status sah di reputation_events.
     * Mengembalikan 'UNREVIEWED' jika belum pernah ditinjau atau belum ada peristiwa evaluasi.
     */
    public function getReputationStatusAttribute(): string
    {
        $lastEvent = $this->reputationEvents()
            ->whereNotNull('new_status')
            ->latest('reputation_event_id')
            ->first();

        if ($lastEvent && in_array($lastEvent->new_status, ['LEGIT', 'PROBLEMATIC'], true)) {
            return $lastEvent->new_status;
        }

        return 'UNREVIEWED';
    }

    /**
     * Total transaksi selesai (baik sebagai buyer maupun seller).
     */
    public function getCompletedTransactionsCountAttribute(): int
    {
        return Transaction::query()
            ->whereHas('agreement', function ($query) {
                $query->where('buyer_id', $this->user_id)
                    ->orWhere('seller_id', $this->user_id);
            })
            ->where('status', 'COMPLETED')
            ->count();
    }

    /**
     * Transaksi selesai sebagai Seller.
     */
    public function getSellerTransactionsCountAttribute(): int
    {
        return Transaction::query()
            ->whereHas('agreement', fn ($query) => $query->where('seller_id', $this->user_id))
            ->where('status', 'COMPLETED')
            ->count();
    }

    /**
     * Transaksi selesai sebagai Buyer.
     */
    public function getBuyerTransactionsCountAttribute(): int
    {
        return Transaction::query()
            ->whereHas('agreement', fn ($query) => $query->where('buyer_id', $this->user_id))
            ->where('status', 'COMPLETED')
            ->count();
    }

    /**
     * Total transaksi aktif yang sedang berjalan.
     */
    public function getActiveTransactionsCountAttribute(): int
    {
        return Transaction::query()
            ->whereHas('agreement', function ($query) {
                $query->where('buyer_id', $this->user_id)
                    ->orWhere('seller_id', $this->user_id);
            })
            ->whereIn('status', [
                'WAITING_PAYMENT',
                'PAID',
                'DELIVERED',
                'CONFIRMED',
                'DISPUTED',
            ])
            ->count();
    }

    /**
     * Total listing marketplace yang aktif dijual.
     */
    public function getActiveListingsCountAttribute(): int
    {
        return $this->listings()->where('status', 'ACTIVE')->count();
    }

    /**
     * Total postingan forum komunitas yang aktif.
     */
    public function getForumPostsCountAttribute(): int
    {
        return $this->forumPosts()->where('status', 'ACTIVE')->count();
    }

    public function hasListings(): bool
    {
        return $this->listings()->exists();
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_id', 'user_id');
    }

    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'author_id', 'user_id');
    }

    public function forumComments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'author_id', 'user_id');
    }

    public function forumLikes(): HasMany
    {
        return $this->hasMany(ForumLike::class, 'user_id', 'user_id');
    }

    public function buyerAgreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'buyer_id', 'user_id');
    }

    public function sellerAgreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'seller_id', 'user_id');
    }

    public function createdAgreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'created_by', 'user_id');
    }

    public function complaintsRaised(): HasMany
    {
        return $this->hasMany(Complaint::class, 'raised_by', 'user_id');
    }

    public function accountReportsReceived(): HasMany
    {
        return $this->hasMany(AccountReport::class, 'reported_user_id', 'user_id');
    }

    public function accountReportsMade(): HasMany
    {
        return $this->hasMany(AccountReport::class, 'reporter_id', 'user_id');
    }

    public function reputationEvents(): HasMany
    {
        return $this->hasMany(ReputationEvent::class, 'user_id', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function evidenceFiles(): HasMany
    {
        return $this->hasMany(EvidenceFile::class, 'uploaded_by', 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id', 'user_id');
    }

    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(SellerPayoutAccount::class, 'seller_id', 'user_id');
    }

    public function activePayoutAccount(): HasOne
    {
        return $this->hasOne(SellerPayoutAccount::class, 'seller_id', 'user_id')->where('is_active', true);
    }
}
