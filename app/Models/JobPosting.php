<?php

namespace App\Models;

use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $position
 * @property string $island
 * @property string $employment_type
 * @property string $summary
 * @property string|null $description
 * @property string|null $duties
 * @property string|null $requirements
 * @property int $openings
 * @property string $status
 * @property bool $is_featured
 * @property Carbon|null $closes_on
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    public const array STATUSES = ['draft' => 'Draft', 'open' => 'Open', 'paused' => 'Paused', 'closed' => 'Closed'];

    public const array EMPLOYMENT_TYPES = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract', 'seasonal' => 'Seasonal'];

    protected $fillable = [
        'slug', 'title', 'position', 'island', 'employment_type', 'summary', 'description', 'duties', 'requirements',
        'openings', 'status', 'is_featured', 'closes_on', 'published_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'closes_on' => 'date',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'openings' => 'integer',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (self $job): void {
            if (! $job->slug) {
                $base = Str::slug($job->title.' '.(config('candid.islands.'.$job->island.'.short') ?? '')) ?: 'job';
                $slug = $base;
                for ($i = 2; static::where('slug', $slug)->whereKeyNot($job->id)->exists(); $i++) {
                    $slug = $base.'-'.$i;
                }
                $job->slug = $slug;
            }
            if ($job->status === 'open' && ! $job->published_at) {
                $job->published_at = now();
            }
        });
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Open and not past its closing date.

     *

     * @param  Builder<self>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', 'open')
            ->where(fn (Builder $q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()));
    }

    public function isLive(): bool
    {
        return $this->status === 'open' && (! $this->closes_on || $this->closes_on->endOfDay()->isFuture());
    }

    public function islandName(): string
    {
        return island_name($this->island);
    }

    public function positionLabel(): string
    {
        return Application::POSITIONS[$this->position] ?? ucfirst($this->position);
    }

    public function typeLabel(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? $this->employment_type;
    }

    /** @return list<string> */
    public function lines(string $field): array
    {
        return lines_to_array((string) $this->getAttribute($field));
    }
}
