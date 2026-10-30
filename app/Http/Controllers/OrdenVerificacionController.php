<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Illuminate\View\View;

class OrdenVerificacionController extends Controller
{
    public function __invoke(string $token): View
    {
        $orden = Orden::query()
            ->where('verificacion_token', $token)
            ->firstOrFail();

        $orden->load(['persona', 'almacen', 'productos.producto']);

        return view('ordenes.verificacion', ['orden' => $orden]);
    }
}
