<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewServiceRequest extends Notification
{
    public function __construct(public readonly ServiceRequest $request) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request;
        $mail = (new MailMessage)
            ->subject("{$r->kindLabel()} {$r->reference}: ".($r->company ?: $r->name).($r->island ? ' ('.island_name($r->island).')' : ''))
            ->line(trim($r->name.($r->company ? ", {$r->company}" : '')).' · '.($r->phone ?: $r->email));

        if ($r->kind === 'quote') {
            $mail->line('Site: '.($r->option('site_type', ServiceRequest::SITE_TYPES) ?? '—').' · Posts: '.($r->detail('posts') ?? '—').' · '.($r->option('coverage', ServiceRequest::COVERAGE) ?? ''));
        } elseif ($r->kind === 'pickup') {
            $mail->line('Pickup '.($r->needed_on?->format('D j M Y') ?? '').' · '.($r->option('window', ServiceRequest::WINDOWS) ?? ''))
                ->line('From: '.($r->detail('pickup_address') ?? '—').' → '.($r->detail('destination') ?? '—'));
        }
        if ($r->message) {
            $mail->line($r->message);
        }

        return $mail->action('Open the operations board', route('admin.requests.show', $r))->replyTo($r->email, $r->name);
    }
}
