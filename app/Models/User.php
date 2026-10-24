<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Staff account. Roles: admin (everything), recruiter (careers back office),
 * operations (client quotes and armoured-transport pickups).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const array ROLES = [
        'admin' => 'Administrator',
        'recruiter' => 'Recruiter',
        'operations' => 'Operations',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Admins can do everything; other roles only their own area. */
    public function hasRole(string ...$roles): bool
    {
        return $this->isAdmin() || in_array($this->role, $roles, true);
    }

    public function canRecruit(): bool
    {
        return $this->hasRole('recruiter');
    }

    public function canOperate(): bool
    {
        return $this->hasRole('operations');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }
}
