<?php

use App\Models\Setting;

if (! function_exists('candid')) {
    function candid(string $key, mixed $default = null): mixed
    {
        return config('candid.'.$key, $default);
    }
}

if (! function_exists('setting')) {
    /** Editable office detail from the settings table (Admin → Settings), cached per request. */
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('initials')) {
    /** "Cleveland A. Duncombe" → "CD". */
    function initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: [], fn (string $w) => $w !== ''));
        if ($words === []) {
            return '';
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr((string) end($words), 0, 1) : ''));
    }
}

if (! function_exists('island_name')) {
    function island_name(?string $key): string
    {
        return (string) (config('candid.islands.'.$key.'.name') ?? ($key ? ucwords(str_replace('_', ' ', $key)) : '—'));
    }
}

if (! function_exists('us_digits')) {
    /** E.164 digits for a North American (incl. Bahamas 242) number written any common way, or null. */
    function us_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return strlen($digits) === 11 && $digits[0] === '1' ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    function tel_href(?string $number): string
    {
        $digits = us_digits($number);

        return $digits ? 'tel:+'.$digits : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);
    }
}

if (! function_exists('format_phone')) {
    /** 242-352-5499, the way Bahamian numbers are written locally; the input unchanged otherwise. */
    function format_phone(?string $number): string
    {
        $digits = us_digits($number);
        if (! $digits) {
            return (string) $number;
        }

        return sprintf('%s-%s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1.3K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('service_keys')) {
    /** @return list<string> */
    function service_keys(): array
    {
        return array_keys((array) config('candid.services'));
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img (WebP at 480/960/1920 wide).
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('candid.photos.'.$key) ?? ['w' => 1920, 'h' => 1280];
        $url = fn (int $w) => asset("img/{$key}-{$w}.webp");

        return [
            'src' => $url(960),
            'srcset' => $url(480).' 480w, '.$url(960).' 960w, '.$url(1920).' 1920w',
            'width' => (int) $meta['w'],
            'height' => (int) $meta['h'],
        ];
    }
}
