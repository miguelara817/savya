<?php

namespace App\Enums;

enum OrdenEstadoCodigo: string
{
    case Borrador = 'BORRADOR';
    case PendienteAprobacion = 'PENDIENTE_APROBACION';
    case Aprobada = 'APROBADA';
    case Pagado = 'PAGADO';
    case Verificado = 'VERIFICADO';
    case ParcialmenteAtendida = 'PARCIALMENTE_ATENDIDA';
    case Completada = 'COMPLETADA';
    case Rechazada = 'RECHAZADA';
    case Cancelada = 'CANCELADA';

    public function esFinal(): bool
    {
        return in_array($this, [
            self::Completada,
            self::Rechazada,
            self::Cancelada,
        ], true);
    }

    public function permitePrepararSalida(): bool
    {
        return in_array($this, [
            self::Aprobada,
            self::Pagado,
            self::Verificado,
            self::ParcialmenteAtendida,
        ], true);
    }
}
