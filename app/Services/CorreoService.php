<?php

namespace App\Services;

use App\Mail\CorreoGenerico;
use Illuminate\Support\Facades\Mail;

class CorreoService
{
    /**
     * @param  array<int, string>  $destinatarios
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $cco
     */
    public function enviar(
        array $destinatarios,
        string $asunto,
        string $cuerpo,
        array $cc = [],
        array $cco = [],
    ): void {
        if ($destinatarios === []) {
            return;
        }

        $correo = Mail::to($destinatarios);

        if ($cc !== []) {
            $correo->cc($cc);
        }

        if ($cco !== []) {
            $correo->bcc($cco);
        }

        $correo->send(new CorreoGenerico($asunto, $cuerpo));
    }
}
