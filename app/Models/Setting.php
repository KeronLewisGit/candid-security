<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Key/value office details editable in Admin → Settings. Defaults (and the
 * label/help shown in the admin) live in Setting::FIELDS.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    /**
     * Every editable setting: [label, default, help]. Defaults are the sourced
     * values from candidbahamas.com; empty means "hide on the site".
     *
     * @var array<string, array{0: string, 1: string, 2?: string}>
     */
    public const array FIELDS = [
        'freeport_phone' => ['Freeport phone', '242-352-5499'],
        'freeport_fax' => ['Freeport fax', '242-352-5489', 'Leave empty to hide.'],
        'freeport_address' => ['Freeport address', "Polaris Drive\nFreeport, Grand Bahama", 'New Polaris Drive head office (The Tribune, Aug 2016). Confirm the street number.'],
        'freeport_hours' => ['Freeport office hours', '', 'Leave empty to show “Call for office hours”.'],
        'nassau_phone' => ['Nassau phone', '242-361-5499'],
        'nassau_fax' => ['Nassau fax', '242-361-5490', 'Leave empty to hide.'],
        'nassau_address' => ['Nassau address', "#8 Carmichael Road Plaza\nNassau, New Providence"],
        'nassau_hours' => ['Nassau office hours', '', 'Leave empty to show “Call for office hours”.'],
        'general_email' => ['General email', 'president@candidbahamas.net', 'Shown on Contact.'],
        'hr_email' => ['Recruitment email', 'hr@candidbahamas.net', 'Shown on Careers.'],
        'facebook_url' => ['Facebook page', '', 'Full URL. Leave empty to hide.'],
        'exam_pass_mark' => ['Exam pass mark (%)', '70', 'The 2011 application form sets 70%. Scores below this are flagged on the applicant page.'],
        'careers_note' => ['Careers notice', '', 'Optional line shown at the top of Careers, e.g. a recruitment drive date.'],
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            try {
                self::$cache = Schema::hasTable('settings') ? self::query()->pluck('value', 'key')->all() : [];
            } catch (Throwable) {
                self::$cache = [];
            }
        }

        $value = self::$cache[$key] ?? null;
        if ($value === null) {
            return $default ?? (self::FIELDS[$key][1] ?? null);
        }

        return $value;
    }

    public static function put(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        self::flush();
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
