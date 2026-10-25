<?php

namespace App\Services;

use App\Models\Orden;

class OrdenPagoCorreoService
{
    public function __construct(public CorreoService $correoService) {}

    public function enviar(Orden $orden): void
    {
        if (blank($orden->correo_facturacion)) {
            return;
        }

        $lineas = $orden->productos()
            ->with('producto')
            ->get()
            ->map(fn ($linea): string => sprintf(
                '<li>%s — %d × Bs %s</li>',
                e($linea->producto?->nombre ?? 'Producto'),
                $linea->cantidad_solicitada,
                number_format((float) $linea->precio_unitario, 2, '.', ','),
            ))
            ->implode('');
        $codigoQr = file_exists(public_path('images/qr-pago.png'))
            ? '<p>Escanee el siguiente código QR para realizar el pago:</p><p><img src="'.e(asset('images/qr-pago.png')).'" alt="QR de pago" style="max-width: 240px;"></p>'
            : '<p>Consulte el código QR de pago en el sistema.</p>';

        $cuerpo = sprintf(
            '<h1>Orden aprobada: %s</h1><p>Su orden ha sido aprobada. Puede proceder con el pago para continuar con el pedido.</p><p><strong>Total: Bs %s</strong></p>%s<ul>%s</ul><p>Razón social: %s<br>NIT: %s</p>
            <strong>***Recuerda que una vez realizado el pago, debe completar el registro en la plataforma.</strong>',
            e($orden->numero ?? $orden->codigo_orden ?? (string) $orden->getKey()),
            number_format((float) $orden->precio_total, 2, '.', ','),
            $codigoQr,
            $lineas,
            e($orden->razon_social ?? ''),
            e($orden->nit ?? ''),
        );

        $this->correoService->enviar(
            destinatarios: [$orden->correo_facturacion],
            asunto: 'Orden de pago '.$orden->numero,
            cuerpo: $cuerpo,
        );
    }
}
