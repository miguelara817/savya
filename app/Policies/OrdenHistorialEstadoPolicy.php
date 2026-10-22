<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrdenHistorialEstado;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrdenHistorialEstadoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrdenHistorialEstado');
    }

    public function view(AuthUser $authUser, OrdenHistorialEstado $ordenHistorialEstado): bool
    {
        return $authUser->can('View:OrdenHistorialEstado');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrdenHistorialEstado');
    }

    public function update(AuthUser $authUser, OrdenHistorialEstado $ordenHistorialEstado): bool
    {
        return $authUser->can('Update:OrdenHistorialEstado');
    }

    public function delete(AuthUser $authUser, OrdenHistorialEstado $ordenHistorialEstado): bool
    {
        return $authUser->can('Delete:OrdenHistorialEstado');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrdenHistorialEstado');
    }

    public function restore(AuthUser $authUser, OrdenHistorialEstado $ordenHistorialEstado): bool
    {
        return $authUser->can('Restore:OrdenHistorialEstado');
    }

    public function forceDelete(AuthUser $authUser, OrdenHistorialEstado $ordenHistorialEstado): bool
    {
        return $authUser->can('ForceDelete:OrdenHistorialEstado');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrdenHistorialEstado');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrdenHistorialEstado');
    }

    public function replicate(AuthUser $authUser, OrdenHistorialEstado $ordenHistorialEstado): bool
    {
        return $authUser->can('Replicate:OrdenHistorialEstado');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrdenHistorialEstado');
    }
}
