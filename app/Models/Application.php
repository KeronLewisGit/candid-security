<?php

namespace App\Models;

use App\Services\ApplicantNotifier;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A job application. Starts as a draft (submitted_at null) saved step by step,
 * then moves through the guard-hiring pipeline in STAGES.
 *
 * @property int $id
 * @property string $reference
 * @property int $applicant_id
 * @property int|null $job_posting_id
 * @property int $step
 * @property Carbon|null $submitted_at
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $island
 * @property string|null $settlement
 * @property string|null $citizenship
 * @property string|null $emergency_name
 * @property string|null $emergency_phone
 * @property string|null $position
 * @property list<string>|null $work_islands
 * @property string|null $employment_type
 * @property list<string>|null $shifts
 * @property Carbon|null $available_from
 * @property string|null $drivers_licence
 * @property bool $firearm_licence
 * @property bool $guard_licence
 * @property int $experience_years
 * @property string|null $last_employer
 * @property string|null $education
 * @property list<string>|null $certifications
 * @property string|null $message
 * @property string $status
 * @property Carbon|null $status_changed_at
 * @property int|null $exam_score
 * @property Carbon|null $exam_taken_on
 * @property string|null $drug_test_result
 * @property Carbon|null $drug_test_on
 * @property string|null $background_result
 * @property int|null $rating
 * @property int|null $assigned_to
 * @property string $source
 * @property Carbon|null $consented_at
 * @property string|null $ip
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $full_name
 * @property-read Applicant $applicant
 * @property-read JobPosting|null $job
 * @property-read User|null $assignee
 * @property-read Collection<int, ApplicationDocument> $documents
 * @property-read Collection<int, ScreeningBooking> $bookings
 */
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /** Guard-hiring pipeline, in order. */
    public const array STAGES = [
        'new' => 'New',
        'document_check' => 'Document check',
        'entrance_exam' => 'Entrance exam',
        'drug_test' => 'Drug test',
        'interview' => 'Interview',
        'background_check' => 'Background check',
        'offer' => 'Offer',
        'hired' => 'Hired',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
    ];

    public const array ACTIVE_STAGES = ['new', 'document_check', 'entrance_exam', 'drug_test', 'interview', 'background_check', 'offer'];

    public const array CLOSED_STAGES = ['hired', 'rejected', 'withdrawn'];

    /** Stages where the applicant books a screening slot of this kind. */
    public const array BOOKABLE = ['entrance_exam' => 'exam', 'drug_test' => 'drug_test', 'interview' => 'interview'];

    /**
     * What the applicant sees in the portal: [headline, what happens now].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const array PUBLIC_STAGES = [
        'new' => ['Application received', 'Your application is in the queue for the recruitment team.'],
        'document_check' => ['Checking your documents', 'We are checking your police certificate and ID. Upload anything that is missing or was sent back.'],
        'entrance_exam' => ['Entrance exam', 'You are invited to sit the Candid entrance exam. Choose a date below.'],
        'drug_test' => ['Drug test', 'You are invited for a drug test. Choose a time below.'],
        'interview' => ['Interview', 'You are invited to interview. Choose a time below.'],
        'background_check' => ['Background check', 'We are completing your background check. No action is needed from you.'],
        'offer' => ['Offer', 'Congratulations, we would like to offer you a position. The recruitment team will contact you.'],
        'hired' => ['Hired', 'Welcome to Candid Security. Watch for your start date and uniform fitting details.'],
        'rejected' => ['Not successful this time', 'Thank you for applying. You are welcome to apply again for future openings.'],
        'withdrawn' => ['Withdrawn', 'This application has been withdrawn.'],
    ];

    public const array POSITIONS = [
        'guard_unarmed' => 'Security officer (unarmed)',
        'guard_armed' => 'Security officer (armed)',
        'armored' => 'Armoured transport crew',
        'patrol' => 'Mobile patrol officer',
        'aviation' => 'Aviation security officer',
        'lifeguard' => 'Lifeguard',
        'supervisor' => 'Supervisor',
        'office' => 'Office / administration',
    ];

    public const array CITIZENSHIP = [
        'bahamian' => 'Bahamian citizen',
        'resident' => 'Permanent resident',
        'permit' => 'Work permit holder',
        'other' => 'Other',
    ];

    public const array EMPLOYMENT = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'either' => 'Either'];

    /** The three shifts on the paper form, plus weekends. */
    public const array SHIFTS = ['day' => '8 am – 4 pm', 'evening' => '4 pm – midnight', 'night' => 'Midnight – 8 am', 'weekends' => 'Weekends'];

    public const array LICENCES = ['none' => 'No driver’s licence', 'private' => 'Private driver’s licence', 'commercial' => 'Commercial / public service licence'];

    public const array EDUCATION = [
        'none' => 'Did not finish high school',
        'high_school' => 'High school graduate',
        'bgcse' => 'High school graduate with BGCSEs',
        'college' => 'College / BTVI / university',
    ];

    public const array CERTIFICATIONS = [
        'first_aid' => 'First aid / CPR',
        'firearms_training' => 'Firearms training',
        'lifeguard' => 'Lifeguard certificate',
        'aviation' => 'Aviation security training',
        'security_course' => 'Security officer course',
    ];

    public const array DRUG_RESULTS = ['pending' => 'Pending', 'negative' => 'Negative (pass)', 'positive' => 'Positive (fail)', 'inconclusive' => 'Inconclusive'];

    public const array BACKGROUND_RESULTS = ['pending' => 'In progress', 'clear' => 'Clear', 'flagged' => 'Flagged'];

    /** Without these two an application cannot pass the document check. */
    public const array REQUIRED_DOCUMENTS = ['police_certificate', 'passport'];

    public const array SOURCES = ['website' => 'Website', 'walk_in' => 'Walk-in', 'referral' => 'Referral', 'facebook' => 'Facebook', 'other' => 'Other'];

    protected $fillable = [
        'applicant_id', 'job_posting_id', 'step', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'island', 'settlement',
        'citizenship', 'emergency_name', 'emergency_phone', 'position', 'work_islands', 'employment_type', 'shifts', 'available_from', 'drivers_licence', 'firearm_licence',
        'guard_licence', 'experience_years', 'last_employer', 'education', 'certifications', 'message', 'source', 'ip',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'date_of_birth' => 'date',
            'work_islands' => 'array',
            'shifts' => 'array',
            'available_from' => 'date',
            'firearm_licence' => 'boolean',
            'guard_licence' => 'boolean',
            'certifications' => 'array',
            'status_changed_at' => 'datetime',
            'exam_taken_on' => 'date',
            'drug_test_on' => 'date',
            'consented_at' => 'datetime',
            'experience_years' => 'integer',
            'exam_score' => 'integer',
            'rating' => 'integer',
            'step' => 'integer',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $a): void {
            $a->reference ??= self::generateReference();
            $a->status_changed_at ??= now();
        });
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'CSL-'.strtoupper(Str::random(6));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    // ---- Relations ----

    /** @return BelongsTo<Applicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /** @return BelongsTo<JobPosting, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<ApplicationNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class)->latest('id');
    }

    /** @return HasMany<ApplicationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class)->latest('id');
    }

    /** @return HasMany<ScreeningBooking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(ScreeningBooking::class);
    }

    // ---- Scopes ----

    /** @param Builder<self> $q */
    public function scopeSubmitted(Builder $q): void
    {
        $q->whereNotNull('submitted_at');
    }

    /** @param Builder<self> $q */
    public function scopeActive(Builder $q): void
    {
        $q->whereNotNull('submitted_at')->whereIn('status', self::ACTIVE_STAGES);
    }

    /**
     * At least one required document has no pending or accepted upload.

     *

     * @param  Builder<self>  $q
     */
    public function scopeMissingDocuments(Builder $q): void
    {
        $q->where(function (Builder $o) {
            foreach (self::REQUIRED_DOCUMENTS as $type) {
                $o->orWhereDoesntHave('documents', fn (Builder $d) => $d->where('type', $type)->where('status', '!=', 'rejected'));
            }
        });
    }

    /**
     * Admin list, pipeline and CSV export share these filters.
     *
     * @param  Builder<self>  $q
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $q, array $f): void
    {
        $q->when(input_string($f['island'] ?? null), fn (Builder $w, string $v) => $w->where('island', $v))
            ->when(input_string($f['stage'] ?? null), fn (Builder $w, string $v) => $w->where('status', $v))
            ->when(input_string($f['position'] ?? null), fn (Builder $w, string $v) => $w->where('position', $v))
            ->when(input_string($f['job'] ?? null), fn (Builder $w, string $v) => $w->where('job_posting_id', (int) $v))
            ->when(input_string($f['assigned'] ?? null), fn (Builder $w, string $v) => $v === 'none' ? $w->whereNull('assigned_to') : $w->where('assigned_to', (int) $v))
            ->when(input_string($f['docs'] ?? null) === 'missing', fn (Builder $w) => $w->missingDocuments())
            ->when(input_string($f['q'] ?? null), function (Builder $w, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $w->where(function (Builder $o) use ($like, $term) {
                    $o->where('reference', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('settlement', 'like', $like)
                        ->orWhere(function (Builder $n) use ($term) {
                            foreach (preg_split('/\s+/', $term) ?: [] as $word) {
                                $n->where(fn (Builder $p) => $p->where('first_name', 'like', '%'.$word.'%')->orWhere('last_name', 'like', '%'.$word.'%'));
                            }
                        });
                });
            });
    }

    // ---- Presentation ----

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function isDraft(): bool
    {
        return $this->submitted_at === null;
    }

    public function isActive(): bool
    {
        return ! $this->isDraft() && in_array($this->status, self::ACTIVE_STAGES, true);
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->status] ?? ucfirst($this->status);
    }

    /** Position of the current stage in the active pipeline (0-based), or null when closed. */
    public function stageIndex(): ?int
    {
        $i = array_search($this->status, self::ACTIVE_STAGES, true);

        return $i === false ? null : $i;
    }

    public function positionLabel(): string
    {
        return self::POSITIONS[$this->position] ?? ($this->job->title ?? 'General application');
    }

    public function roleTitle(): string
    {
        return $this->job->title ?? $this->positionLabel();
    }

    public function islandName(): string
    {
        return island_name($this->island);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function examPassed(): ?bool
    {
        return $this->exam_score === null ? null : $this->exam_score >= (int) setting('exam_pass_mark', '70');
    }

    /**
     * Required document types with no usable (pending or accepted) upload.
     *
     * @return list<string>
     */
    public function missingDocuments(): array
    {
        $have = $this->documents->where('status', '!=', 'rejected')->pluck('type')->all();

        return array_values(array_diff(self::REQUIRED_DOCUMENTS, $have));
    }

    public function documentsComplete(): bool
    {
        return $this->missingDocuments() === [];
    }

    /** The screening kind the applicant may book now, if any. */
    public function bookableKind(): ?string
    {
        return self::BOOKABLE[$this->status] ?? null;
    }

    public function bookingFor(string $kind): ?ScreeningBooking
    {
        return $this->bookings->firstWhere('kind', $kind);
    }

    public function canBook(): bool
    {
        $kind = $this->bookableKind();

        return $kind !== null && $this->bookingFor($kind) === null;
    }

    // ---- Pipeline ----

    /** Move to a stage, write the timeline and tell the applicant. */
    public function moveTo(string $status, ?User $by = null, ?string $comment = null): bool
    {
        if (! array_key_exists($status, self::STAGES) || $status === $this->status || $this->isDraft()) {
            return false;
        }
        $from = $this->status;
        $this->forceFill(['status' => $status, 'status_changed_at' => now()])->save();
        if (in_array($status, self::CLOSED_STAGES, true)) {
            // Hand the seat back so someone else can book that exam, drug test or interview.
            $this->bookings()->whereHas('slot', fn (Builder $s) => $s->where('starts_at', '>', now()))->delete();
            $this->unsetRelation('bookings');
        }
        $this->notes()->create([
            'user_id' => $by?->id,
            'type' => 'status',
            'is_public' => true,
            'body' => self::PUBLIC_STAGES[$status][0],
            'meta' => ['from' => $from, 'to' => $status, 'comment' => $comment],
        ]);
        app(ApplicantNotifier::class)->stageChanged($this);

        return true;
    }

    public function submit(?string $ip): void
    {
        $this->forceFill([
            'submitted_at' => now(),
            'status' => 'new',
            'status_changed_at' => now(),
            'consented_at' => now(),
            'ip' => $ip,
        ])->save();
        $this->notes()->create(['type' => 'system', 'is_public' => true, 'body' => 'Application submitted online']);
    }
}
