<?php

namespace App\Notifications;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplication extends Notification
{
    public function __construct(public readonly Application $application) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->application;
        $missing = $a->missingDocuments();

        return (new MailMessage)
            ->subject("New application {$a->reference}: {$a->full_name}, {$a->roleTitle()} ({$a->islandName()})")
            ->line("{$a->full_name} applied for {$a->roleTitle()} on {$a->islandName()}.")
            ->line('Phone: '.$a->phone.' · Email: '.$a->email)
            ->line($missing === [] ? 'Police certificate and ID uploaded: ready for document check.' : 'Still missing: '.implode(', ', array_map(fn ($t) => ApplicationDocument::TYPES[$t], $missing)).'.')
            ->action('Open the application', route('admin.applications.show', $a));
    }
}
