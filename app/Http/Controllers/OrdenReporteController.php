<?php

namespace App\Http\Controllers;

use App\Enums\OrdenEstadoCodigo;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdenReporteController extends Controller
{
    public function __invoke(Request $request, Orden $orden): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario instanceof User
                && ($usuario->hasRole('super_admin')
                    || ($usuario->hasRole('cliente')
                        && (int) $orden->solicitante_user_id === (int) $usuario->getKey()))
                && $orden->estaEnEstado(OrdenEstadoCodigo::PendienteAprobacion),
            Response::HTTP_NOT_FOUND,
        );

        $orden->loadMissing(['almacen', 'persona', 'productos.producto']);

        $pdf = app('dompdf.wrapper')
            ->loadView('ordenes.reporte', ['orden' => $orden])
            ->setPaper('letter');

        return $request->boolean('descargar')
            ? $pdf->download('orden-'.$orden->numero.'.pdf')
            : $pdf->stream('orden-'.$orden->numero.'.pdf');
    }
}
