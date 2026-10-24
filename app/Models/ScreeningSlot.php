<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A sitting of the entrance exam, a drug-test session or an interview block.
 * Applicants at the matching stage book a seat from their portal.
 *
 * @property int $id
 * @property string $kind
 * @property string $island
 * @property Carbon $starts_at
 * @property int $duration_minutes
 * @property string $location
 * @property int $capacity
 * @property string|null $notes
 * @property int|null $bookings_count
 */
class ScreeningSlot extends Model
{
    public const array KINDS = ['exam' => 'Entrance exam', 'drug_test' => 'Drug test', 'interview' => 'Interview'];

    /** Bookings close this many hours before the start. */
    public const int CUTOFF_HOURS = 2;

    protected $fillable = ['kind', 'island', 'starts_at', 'duration_minutes', 'location', 'capacity', 'notes', 'created_by'];

    #[\Override]
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'capacity' => 'integer', 'duration_minutes' => 'integer'];
    }

    /** @return HasMany<ScreeningBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(ScreeningBooking::class);
    }

    /**
     * Future slots with a free seat.

     *

     * @param  Builder<self>  $q
     */
    public function scopeAvailable(Builder $q, string $kind, ?string $island = null): void
    {
        $q->where('kind', $kind)
            ->when($island, fn (Builder $w, string $i) => $w->where('island', $i))
            ->where('starts_at', '>', now()->addHours(self::CUTOFF_HOURS))
            ->withCount('bookings')
            ->whereRaw('capacity > (select count(*) from screening_bookings where screening_bookings.screening_slot_id = screening_slots.id)')
            ->orderBy('starts_at');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - ($this->bookings_count ?? $this->bookings()->count()));
    }

    public function isBookable(): bool
    {
        return $this->starts_at->gt(now()->addHours(self::CUTOFF_HOURS)) && $this->seatsLeft() > 0;
    }
}
