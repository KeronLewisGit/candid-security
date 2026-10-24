<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ScreeningBooking;
use App\Notifications\ApplicantUpdate;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Tells an applicant something happened: writes the portal inbox entry and
 * emails them. A failing mail server is noted on the timeline instead of
 * breaking the recruiter's action.
 */
class ApplicantNotifier
{
    public function stageChanged(Application $application): void
    {
        [$headline, $now] = Application::PUBLIC_STAGES[$application->status] ?? ['Application updated', ''];
        $title = match ($application->status) {
            'entrance_exam', 'drug_test', 'interview' => 'Next step: '.strtolower($headline),
            'rejected' => 'An update on your application',
            default => $headline,
        };
        $body = 'Your application for '.$application->roleTitle().' ('.$application->reference.') has moved to: '.$headline.".\n\n".$now;
        if ($application->canBook()) {
            $body .= "\n\nSign in to your applicant portal to choose a time that suits you.";
        }

        $this->send($application, $title, $body);
    }

    public function applicationReceived(Application $application): void
    {
        $missing = $application->missingDocuments();
        $body = 'Thank you for applying for '.$application->roleTitle().'. Your reference is '.$application->reference.'.';
        if ($missing !== []) {
            $body .= "\n\nBefore we can check your documents we still need: "
                .implode(' and ', array_map(fn (string $t) => ApplicationDocument::TYPES[$t], $missing))
                .'. You can upload them from your applicant portal at any time.';
        }

        $this->send($application, 'Application received: '.$application->reference, $body);
    }

    public function booked(ScreeningBooking $booking): void
    {
        $slot = $booking->slot;
        $application = $booking->application;
        $body = 'You are booked for the '.strtolower($slot->kindLabel()).' on '.$slot->starts_at->format('l j F Y \a\t g:i a')
            .' at '.$slot->location.'.'."\n\n".$this->bring($slot->kind);

        $this->send($application, $slot->kindLabel().' booked', $body);
    }

    public function documentReviewed(ApplicationDocument $document): void
    {
        if ($document->status !== 'rejected') {
            return;
        }
        $body = 'Your '.$document->typeLabel().' could not be accepted'.($document->review_note ? ': '.$document->review_note : '.')
            ."\n\nPlease upload a new copy from your applicant portal.";

        $this->send($document->application, 'Please upload your '.strtolower($document->typeLabel()).' again', $body);
    }

    public function message(Application $application, string $subject, string $body): bool
    {
        $this->record($application, $subject, $body);

        return $this->mail($application, $subject, $body);
    }

    private function bring(string $kind): string
    {
        return match ($kind) {
            'exam' => 'Please arrive 15 minutes early with your photo ID and a pen. The pass mark is '.setting('exam_pass_mark', '70').'%.',
            'drug_test' => 'Please bring your photo ID.',
            default => 'Please bring your photo ID and the originals of any certificates you uploaded.',
        };
    }

    private function send(Application $application, string $title, string $body): void
    {
        $this->record($application, $title, $body);
        $this->mail($application, $title, $body);
    }

    private function mail(Application $application, string $title, string $body): bool
    {
        try {
            Notification::route('mail', $application->email)->notify(new ApplicantUpdate(
                $title, $body, route('portal.applications.show', $application->reference), $application->first_name,
            ));

            return true;
        } catch (Throwable $e) {
            report($e);
            $application->notes()->create(['type' => 'system', 'body' => 'Email to applicant failed ('.$title.'): '.$e->getMessage()]);

            return false;
        }
    }

    private function record(Application $application, string $title, string $body): void
    {
        $application->applicant->inbox()->create([
            'application_id' => $application->id,
            'title' => $title,
            'body' => $body,
            'link' => route('portal.applications.show', $application->reference),
        ]);
    }
}
