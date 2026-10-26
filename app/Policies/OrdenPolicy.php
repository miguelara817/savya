<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrdenEstadoCodigo;
use App\Models\Orden;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrdenPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Orden');
    }

    public function view(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('View:Orden')
            && (! $authUser->hasRole('cliente') || (int) $orden->solicitante_user_id === (int) $authUser->getAuthIdentifier());
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Orden');
    }

    public function update(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Update:Orden') && $orden->esEditable();
    }

    public function delete(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Delete:Orden') && $orden->esEditable();
    }

    public function enviarAprobacion(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('EnviarAprobacion:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::Borrador);
    }

    public function aprobar(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Aprobar:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::PendienteAprobacion);
    }

    public function pagar(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Pagar:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::Aprobada)
            && (! $authUser->hasRole('cliente') || (int) $orden->solicitante_user_id === (int) $authUser->getAuthIdentifier());
    }

    public function verificarPago(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('VerificarPago:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::Pagado)
            && filled($orden->comprobante_pago);
    }

    public function entregar(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Entregar:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::Verificado);
    }

    public function rechazar(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Rechazar:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::PendienteAprobacion);
    }

    public function verMotivoRechazo(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('View:Orden')
            && $authUser->can('VerMotivoRechazo:Orden')
            && $orden->estaEnEstado(
                OrdenEstadoCodigo::Rechazada,
                OrdenEstadoCodigo::Cancelada,
                OrdenEstadoCodigo::Aprobada,
            )
            && filled($orden->motivo_rechazo)
            && (! $authUser->hasRole('cliente') || (int) $orden->solicitante_user_id === (int) $authUser->getAuthIdentifier());
    }

    public function cancelar(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Cancelar:Orden')
            && $orden->estaEnEstado(OrdenEstadoCodigo::Aprobada);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Orden');
    }

    public function restore(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Restore:Orden');
    }

    public function forceDelete(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('ForceDelete:Orden') && $orden->esEditable();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Orden');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Orden');
    }

    public function replicate(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Replicate:Orden');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Orden');
    }
}
