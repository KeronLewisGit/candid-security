<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Timeline entry. Public entries are shown to the applicant; recruiter notes never are.
 *
 * @property int $id
 * @property int $application_id
 * @property int|null $user_id
 * @property string $type
 * @property bool $is_public
 * @property string $body
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class ApplicationNote extends Model
{
    protected $fillable = ['user_id', 'type', 'is_public', 'body', 'meta'];

    #[\Override]
    protected function casts(): array
    {
        return ['meta' => 'array', 'is_public' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
