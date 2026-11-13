<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/*
 * Otaku Track memakai tabel `users` legacy dari versi PHP murni:
 * identitas login antara username/email dengan password tersimpan pada kolom `password_hash`.
 * Controller auth kustom membaca kolom-kolom ini, bukan schema default Laravel.
 */
#[Fillable(['username', 'email', 'password_hash', 'role', 'avatar_url', 'bio'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reset_expires_at' => 'datetime',
        ];
    }

    /**
     * Kolom "password" Laravel dipetakan ke kolom legacy `password_hash`.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function mediaLists(): HasMany
    {
        return $this->hasMany(MediaList::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function forumReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    public function forumVotes(): HasMany
    {
        return $this->hasMany(ForumVote::class);
    }

    public function watchLinks(): HasMany
    {
        return $this->hasMany(WatchLink::class, 'created_by');
    }
}
