<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * An upload on an application, kept on the private "local" disk and only
 * streamed through an authorised route (staff, or the applicant who owns it).
 *
 * @property int $id
 * @property int $application_id
 * @property string $type
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property string $status
 * @property string|null $review_note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property-read Application $application
 */
class ApplicationDocument extends Model
{
    /** What the paper form asked applicants to bring to the office, now uploaded instead. */
    public const array TYPES = [
        'police_certificate' => 'Police Character Certificate',
        'passport' => 'Passport (first three pages) or photo ID',
        'photo' => 'Passport-size colour photo',
        'references' => 'Written references',
        'permit' => 'Spouse or work permit',
        'cv' => 'CV / résumé',
    ];

    /** Shown under each upload field. */
    public const array HINTS = [
        'police_certificate' => 'A clear photo or scan of the whole certificate.',
        'passport' => 'The first three pages of your passport, or a voter’s card or driver’s licence.',
        'photo' => 'A recent colour photo of your face against a plain background.',
        'references' => 'Up to three written references in one file, if you have them.',
        'permit' => 'Only if you are not a Bahamian citizen.',
        'cv' => 'Optional. Helpful for supervisor and office roles.',
    ];

    public const array STATUSES = ['pending' => 'Waiting for review', 'accepted' => 'Accepted', 'rejected' => 'Sent back'];

    /** Phone photos and scans are fine. */
    public const array EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'doc', 'docx'];

    public const int MAX_KB = 8192;

    protected $fillable = ['type', 'path', 'original_name', 'mime', 'size', 'status', 'review_note', 'reviewed_by', 'reviewed_at'];

    #[\Override]
    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'size' => 'integer'];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::deleting(fn (self $doc) => Storage::disk('local')->delete($doc->path));
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1048576 ? round($this->size / 1048576, 1).' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }

    /**
     * Validation rules for one upload.

     *

     * @return list<string>
     */
    public static function rules(bool $required = false): array
    {
        $ext = implode(',', self::EXTENSIONS);

        return [$required ? 'required' : 'nullable', 'file', 'max:'.self::MAX_KB, 'extensions:'.$ext, 'mimes:'.$ext];
    }

    public static function store(Application $application, string $type, UploadedFile $file): self
    {
        // Random file name on the private disk; the original name is only shown to staff and the applicant.
        $path = $file->store('applications/'.$application->id, 'local');

        /** @var self $doc */
        $doc = $application->documents()->create([
            'type' => $type,
            'path' => (string) $path,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
            'mime' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'status' => 'pending',
        ]);

        return $doc;
    }
}
