<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class ApplicantResetPassword extends ResetPassword
{
    #[\Override]
    protected function resetUrl($notifiable): string
    {
        return route('portal.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
