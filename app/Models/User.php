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
        $email = strtolower(trim((string) $this->email));

        return collect(config('torrent.admin_emails', []))
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter()
            ->contains($email);
    }
}
