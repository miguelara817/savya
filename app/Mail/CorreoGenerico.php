<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class CorreoGenerico extends Mailable
{
    public function __construct(
        public string $asunto,
        public string $cuerpo,
    ) {}

    public function build(): static
    {
        return $this
            ->subject($this->asunto)
            ->html($this->cuerpo);
    }
}
