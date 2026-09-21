<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'full_name',
        'email',
        'email_verified_at',
        'locale_id',
        'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }

    public function isPortalAdmin(): bool
    {
        return $this->emailInConfig('torrent.admin_emails');
    }

    public function isPortalReviewer(): bool
    {
        return $this->isPortalAdmin() || $this->emailInConfig('torrent.reviewer_emails');
    }

    public function portalRole(): string
    {
        if ($this->isPortalAdmin()) {
            return 'admin';
        }

        if ($this->isPortalReviewer()) {
            return 'reviewer';
        }

        return 'translator';
    }

    private function emailInConfig(string $key): bool
    {
        $email = strtolower(trim((string) $this->email));

        return collect(config($key, []))
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->contains($email);
    }
}
