<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $applicant_id
 * @property int|null $application_id
 * @property string $title
 * @property string|null $body
 * @property string|null $link
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 */
class ApplicantNotification extends Model
{
    protected $fillable = ['application_id', 'title', 'body', 'link', 'read_at'];

    #[\Override]
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
