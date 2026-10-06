<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}
