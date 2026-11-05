<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\OpenWaService;
use Filament\Auth\MultiFactor\Email\Notifications\VerifyEmailAuthentication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;
use Throwable;

class VerifyLoginEmail extends VerifyEmailAuthentication
{
    public function __construct(#[SensitiveParameter] string $code, int $codeExpiryMinutes)
    {
        parent::__construct($code, $codeExpiryMinutes);

        $this->onConnection('sync');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User) {
            try {
                $numero = $notifiable->persona?->telefono;
                // echo $numero;
                if (is_string($numero) && trim($numero) !== '') {
                    app(OpenWaService::class)->enviarMensaje(
                        $numero,
                        "Tu código de verificación de SAVYA es: *{$this->code}*. Vence en {$this->codeExpiryMinutes} minutos.",
                    );
                }
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar el código MFA por WhatsApp.', [
                    'user_id' => $notifiable->getKey(),
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return ['mail'];
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
