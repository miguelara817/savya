<?php

namespace App\Http\Controllers;

use App\Enums\OrdenEstadoCodigo;
use App\Models\Orden;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        if (empty($orden->verificacion_token)) {
            $orden->forceFill(['verificacion_token' => Str::random(64)])->save();
        }

        $orden->loadMissing(['almacen', 'persona', 'productos.producto']);

        $pdf = app('dompdf.wrapper')
            ->loadView('ordenes.reporte', [
                'orden' => $orden,
                'urlVerificacion' => route('ordenes.verificar', ['token' => $orden->verificacion_token]),
                'qrVerificacion' => (new QRCode(new QROptions([
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                    'scale' => 5,
                ])))->render(route('ordenes.verificar', ['token' => $orden->verificacion_token])),
            ])
            ->setPaper('letter');

        return $request->boolean('descargar')
            ? $pdf->download('orden-'.$orden->numero.'.pdf')
            : $pdf->stream('orden-'.$orden->numero.'.pdf');
    }
}
