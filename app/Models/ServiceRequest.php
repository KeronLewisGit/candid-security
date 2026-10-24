<?php

namespace App\Models;

use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Client-side intake: guard-service quotes (RFPs), armoured-transport pickup
 * requests and general contact messages. Worked on the operations board.
 *
 * @property int $id
 * @property string $reference
 * @property string $kind
 * @property string $status
 * @property string $name
 * @property string|null $company
 * @property string $email
 * @property string|null $phone
 * @property string|null $island
 * @property string|null $service
 * @property array<string, mixed>|null $details
 * @property Carbon|null $needed_on
 * @property string|null $message
 * @property string|null $staff_notes
 * @property int|null $assigned_to
 * @property Carbon|null $responded_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read User|null $assignee
 */
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    public const array KINDS = [
        'quote' => ['label' => 'Guard service quotes', 'one' => 'Quote request', 'prefix' => 'Q'],
        'pickup' => ['label' => 'Armoured pickups', 'one' => 'Pickup request', 'prefix' => 'P'],
        'contact' => ['label' => 'Messages', 'one' => 'Message', 'prefix' => 'M'],
    ];

    /** @var array<string, array<string, string>> */
    public const array STATUSES = [
        'quote' => ['new' => 'New', 'contacted' => 'Contacted', 'site_visit' => 'Site visit', 'quoted' => 'Quote sent', 'won' => 'Won', 'lost' => 'Lost'],
        'pickup' => ['new' => 'New', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
        'contact' => ['new' => 'New', 'replied' => 'Replied', 'closed' => 'Closed'],
    ];

    /** Statuses that count as finished (drop off the board). */
    public const array CLOSED = ['won', 'lost', 'completed', 'cancelled', 'closed'];

    public const array SITE_TYPES = [
        'commercial' => 'Office / commercial', 'retail' => 'Retail store', 'industrial' => 'Industrial / port / warehouse',
        'residential' => 'Residential / gated community', 'hotel' => 'Hotel / resort', 'construction' => 'Construction site',
        'event' => 'Event', 'institution' => 'School / church / institution', 'government' => 'Government', 'other' => 'Other',
    ];

    public const array COVERAGE = ['unarmed' => 'Unarmed officers', 'armed' => 'Armed officers', 'both' => 'Both', 'advise' => 'Not sure, please advise'];

    public const array HOURS = ['day' => 'Daytime', 'night' => 'Overnight', '24h' => '24 hours', 'event' => 'Event hours only', 'custom' => 'Other schedule'];

    public const array DURATION = ['ongoing' => 'Ongoing contract', 'short' => 'Short term (under 3 months)', 'one_off' => 'One-off / event'];

    public const array WINDOWS = ['morning' => 'Morning (8–11)', 'midday' => 'Midday (11–2)', 'afternoon' => 'Afternoon (2–5)', 'flexible' => 'Flexible'];

    public const array FREQUENCY = ['one_off' => 'One-off', 'weekly' => 'Weekly', 'several' => 'Several times a week', 'daily' => 'Daily'];

    protected $fillable = [
        'kind', 'status', 'name', 'company', 'email', 'phone', 'island', 'service', 'details', 'needed_on', 'message',
        'staff_notes', 'assigned_to', 'ip',
    ];

    #[\Override]
    protected function casts(): array
    {
        return ['details' => 'array', 'needed_on' => 'date', 'responded_at' => 'datetime', 'status_changed_at' => 'datetime'];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $r): void {
            $r->status ??= 'new';
            $r->status_changed_at ??= now();
            $r->reference ??= 'pending-'.bin2hex(random_bytes(3));
        });
        static::created(function (self $r): void {
            $prefix = self::KINDS[$r->kind]['prefix'] ?? 'R';
            $r->forceFill(['reference' => sprintf('C%s-%s-%04d', $prefix, $r->created_at->format('y'), $r->id)])->saveQuietly();
        });
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @param Builder<self> $q */
    public function scopeOpen(Builder $q): void
    {
        $q->whereNotIn('status', self::CLOSED);
    }

    /**
     * New and untouched for more than 24 hours: the number operations must act on.

     *

     * @param  Builder<self>  $q
     */
    public function scopeOverdue(Builder $q): void
    {
        $q->where('status', 'new')->whereNull('responded_at')->where('created_at', '<', now()->subDay());
    }

    public function isOverdue(): bool
    {
        return $this->status === 'new' && ! $this->responded_at && $this->created_at->lt(now()->subDay());
    }

    /** @return array<string, string> */
    public function statuses(): array
    {
        return self::STATUSES[$this->kind] ?? ['new' => 'New'];
    }

    public function statusLabel(): string
    {
        return $this->statuses()[$this->status] ?? ucfirst($this->status);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind]['one'] ?? ucfirst($this->kind);
    }

    public function serviceLabel(): ?string
    {
        return $this->service ? (config('candid.services.'.$this->service.'.name') ?? $this->service) : null;
    }

    public function detail(string $key): mixed
    {
        return $this->details[$key] ?? null;
    }

    /**
     * Readable label for an option stored in details.

     *

     * @param  array<string, string>  $options
     */
    public function option(string $key, array $options): ?string
    {
        $v = $this->detail($key);

        return is_string($v) ? ($options[$v] ?? $v) : null;
    }

    public function moveTo(string $status): bool
    {
        if (! array_key_exists($status, $this->statuses()) || $status === $this->status) {
            return false;
        }
        $this->status = $status;
        $this->status_changed_at = now();
        if ($status !== 'new') {
            $this->responded_at ??= now();
        }
        $this->save();

        return true;
    }
}
