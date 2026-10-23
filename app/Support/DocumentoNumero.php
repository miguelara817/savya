<?php

namespace App\Support;

use Illuminate\Support\Str;

class DocumentoNumero
{
    public static function generar(string $prefijo): string
    {
        return strtoupper($prefijo).'-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
