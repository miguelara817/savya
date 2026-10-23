<?php

namespace App\Enums;

enum OrdenEstadoCodigo: string
{
    case Borrador = 'BORRADOR';
    case PendienteAprobacion = 'PENDIENTE_APROBACION';
    case Aprobada = 'APROBADA';
    case ParcialmenteAtendida = 'PARCIALMENTE_ATENDIDA';
    case Completada = 'COMPLETADA';
    case Rechazada = 'RECHAZADA';
    case Cancelada = 'CANCELADA';

    /**
     * Estados heredados que se conservan por compatibilidad con registros existentes.
     */
    case Registrado = 'REGISTRADO';
    case Efectivizado = 'EFECTIVIZADO';
    case Anulado = 'ANULADO';

    public function esFinal(): bool
    {
        return in_array($this, [
            self::Completada,
            self::Rechazada,
            self::Cancelada,
            self::Anulado,
        ], true);
    }

    public function permitePrepararSalida(): bool
    {
        return in_array($this, [
            self::Aprobada,
            self::ParcialmenteAtendida,
        ], true);
    }
}
