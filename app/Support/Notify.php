<?php

namespace App\Support;

use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Emails the office. Mail goes out after the response is sent, so a slow or
 * failing mail server never makes an applicant wait or lose a submission.
 */
final class Notify
{
    /** @return list<string> */
    public static function recipients(): array
    {
        return array_values(array_filter(self::configured(), fn (string $a) => (bool) filter_var($a, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * Entries in CANDID_NOTIFY_EMAIL that are not email addresses (typos), shown on the dashboard.
     *
     * @return list<string>
     */
    public static function invalid(): array
    {
        return array_values(array_diff(self::configured(), self::recipients()));
    }

    /** @return list<string> */
    private static function configured(): array
    {
        return preg_split('/[\s,;]+/', (string) config('candid.notify'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public static function office(BaseNotification $notification): void
    {
        $to = self::recipients();

        if ($to === []) {
            Log::warning('CANDID_NOTIFY_EMAIL is not set; nobody was emailed about a new submission.', ['notification' => $notification::class]);

            return;
        }

        if ($skipped = self::invalid()) {
            Log::warning('CANDID_NOTIFY_EMAIL has entries that are not email addresses; they were skipped.', ['skipped' => $skipped]);
        }

        defer(function () use ($to, $notification): void {
            try {
                Notification::route('mail', $to)->notify($notification);
            } catch (Throwable $e) {
                report($e);
            }
        });
    }
}
