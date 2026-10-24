<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email to an applicant: a stage change, a booking or a recruiter's message. */
class ApplicantUpdate extends Notification
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $link,
        public readonly string $firstName,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->greeting('Hello '.$this->firstName.',');
        foreach (preg_split('/\R{2,}/', trim($this->body)) ?: [] as $paragraph) {
            $mail->line($paragraph);
        }
        $mail->action('Open your applicant portal', $this->link)
            ->salutation('Candid Security Recruitment');

        if ($hr = setting('hr_email')) {
            $mail->replyTo($hr, 'Candid Security Recruitment');
        }

        return $mail;
    }
}
