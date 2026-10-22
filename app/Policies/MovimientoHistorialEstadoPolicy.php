<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MovimientoHistorialEstado;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MovimientoHistorialEstadoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MovimientoHistorialEstado');
    }

    public function view(AuthUser $authUser, MovimientoHistorialEstado $movimientoHistorialEstado): bool
    {
        return $authUser->can('View:MovimientoHistorialEstado');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MovimientoHistorialEstado');
    }

    public function update(AuthUser $authUser, MovimientoHistorialEstado $movimientoHistorialEstado): bool
    {
        return $authUser->can('Update:MovimientoHistorialEstado');
    }

    public function delete(AuthUser $authUser, MovimientoHistorialEstado $movimientoHistorialEstado): bool
    {
        return $authUser->can('Delete:MovimientoHistorialEstado');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MovimientoHistorialEstado');
    }

    public function restore(AuthUser $authUser, MovimientoHistorialEstado $movimientoHistorialEstado): bool
    {
        return $authUser->can('Restore:MovimientoHistorialEstado');
    }

    public function forceDelete(AuthUser $authUser, MovimientoHistorialEstado $movimientoHistorialEstado): bool
    {
        return $authUser->can('ForceDelete:MovimientoHistorialEstado');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MovimientoHistorialEstado');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MovimientoHistorialEstado');
    }

    public function replicate(AuthUser $authUser, MovimientoHistorialEstado $movimientoHistorialEstado): bool
    {
        return $authUser->can('Replicate:MovimientoHistorialEstado');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MovimientoHistorialEstado');
    }
}
