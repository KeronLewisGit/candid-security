<?php

namespace App\Models;

use App\Notifications\ApplicantResetPassword;
use Database\Factories\ApplicantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Applicant portal account (guard "applicant"). Separate from staff users.
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $last_login_at
 * @property-read string $full_name
 */
class Applicant extends Authenticatable
{
    /** @use HasFactory<ApplicantFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    #[\Override]
    protected function casts(): array
    {
        return ['password' => 'hashed', 'last_login_at' => 'datetime'];
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class)->latest();
    }

    /** @return HasMany<ApplicantNotification, $this> */
    public function inbox(): HasMany
    {
        return $this->hasMany(ApplicantNotification::class)->latest();
    }

    public function unreadCount(): int
    {
        return $this->inbox()->whereNull('read_at')->count();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @param string $token */
    #[\Override]
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ApplicantResetPassword($token));
    }
}
