<?php

namespace App\Notifications;

use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use SensitiveParameter;

class VerifyLoginEmail extends VerifyEmailAuthentication
{
    public function __construct(#[SensitiveParameter] string $code, int $codeExpiryMinutes)
    {
        parent::__construct($code, $codeExpiryMinutes);

        $this->onConnection('sync');
    }
}
