<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Entrega;
use App\Models\EntregaDetalle;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EntregaDetallePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EntregaDetalle');
    }

    public function view(AuthUser $authUser, EntregaDetalle $entregaDetalle): bool
    {
        return $authUser->can('View:EntregaDetalle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EntregaDetalle');
    }

    public function update(AuthUser $authUser, EntregaDetalle $entregaDetalle): bool
    {
        return $authUser->can('Update:EntregaDetalle')
            && $entregaDetalle->entrega?->estado === Entrega::ESTADO_BORRADOR;
    }

    public function delete(AuthUser $authUser, EntregaDetalle $entregaDetalle): bool
    {
        return $authUser->can('Delete:EntregaDetalle')
            && $entregaDetalle->entrega?->estado === Entrega::ESTADO_BORRADOR;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EntregaDetalle');
    }

    public function restore(AuthUser $authUser, EntregaDetalle $entregaDetalle): bool
    {
        return $authUser->can('Restore:EntregaDetalle');
    }

    public function forceDelete(AuthUser $authUser, EntregaDetalle $entregaDetalle): bool
    {
        return $authUser->can('ForceDelete:EntregaDetalle')
            && $entregaDetalle->entrega?->estado === Entrega::ESTADO_BORRADOR;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EntregaDetalle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EntregaDetalle');
    }

    public function replicate(AuthUser $authUser, EntregaDetalle $entregaDetalle): bool
    {
        return $authUser->can('Replicate:EntregaDetalle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EntregaDetalle');
    }
}
