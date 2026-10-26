<?php

namespace App\Notifications;

use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Illuminate\Notifications\Messages\MailMessage;
use SensitiveParameter;

class VerifyLoginEmail extends VerifyEmailAuthentication
{
    public function __construct(#[SensitiveParameter] string $code, int $codeExpiryMinutes)
    {
        parent::__construct($code, $codeExpiryMinutes);

        $this->onConnection('sync');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de verificación de SAVYA')
            ->view('emails.mfa-verification-code', [
                'code' => $this->code,
                'codeExpiryMinutes' => $this->codeExpiryMinutes,
            ]);
    }
}
