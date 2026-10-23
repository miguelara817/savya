<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SalidaAlmacen;
use App\Models\SalidaDetalle;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SalidaDetallePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalidaDetalle');
    }

    public function view(AuthUser $authUser, SalidaDetalle $salidaDetalle): bool
    {
        return $authUser->can('View:SalidaDetalle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalidaDetalle');
    }

    public function update(AuthUser $authUser, SalidaDetalle $salidaDetalle): bool
    {
        return $authUser->can('Update:SalidaDetalle')
            && $salidaDetalle->salida?->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function delete(AuthUser $authUser, SalidaDetalle $salidaDetalle): bool
    {
        return $authUser->can('Delete:SalidaDetalle')
            && $salidaDetalle->salida?->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SalidaDetalle');
    }

    public function restore(AuthUser $authUser, SalidaDetalle $salidaDetalle): bool
    {
        return $authUser->can('Restore:SalidaDetalle');
    }

    public function forceDelete(AuthUser $authUser, SalidaDetalle $salidaDetalle): bool
    {
        return $authUser->can('ForceDelete:SalidaDetalle')
            && $salidaDetalle->salida?->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalidaDetalle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalidaDetalle');
    }

    public function replicate(AuthUser $authUser, SalidaDetalle $salidaDetalle): bool
    {
        return $authUser->can('Replicate:SalidaDetalle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalidaDetalle');
    }
}
