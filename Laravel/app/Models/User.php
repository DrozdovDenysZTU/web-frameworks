<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public const ROLE_CLIENT = 'client';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ADMIN = 'admin';

    public const ROLE_LEVELS = [
        self::ROLE_CLIENT => 1,
        self::ROLE_MANAGER => 2,
        self::ROLE_ADMIN => 3,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role' => self::ROLE_CLIENT,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string $role): bool
    {
        return (self::ROLE_LEVELS[$this->role] ?? 0) >= self::ROLE_LEVELS[$role];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
