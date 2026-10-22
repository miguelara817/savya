<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrdenEstado;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrdenEstadoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrdenEstado');
    }

    public function view(AuthUser $authUser, OrdenEstado $ordenEstado): bool
    {
        return $authUser->can('View:OrdenEstado');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrdenEstado');
    }

    public function update(AuthUser $authUser, OrdenEstado $ordenEstado): bool
    {
        return $authUser->can('Update:OrdenEstado');
    }

    public function delete(AuthUser $authUser, OrdenEstado $ordenEstado): bool
    {
        return $authUser->can('Delete:OrdenEstado');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrdenEstado');
    }

    public function restore(AuthUser $authUser, OrdenEstado $ordenEstado): bool
    {
        return $authUser->can('Restore:OrdenEstado');
    }

    public function forceDelete(AuthUser $authUser, OrdenEstado $ordenEstado): bool
    {
        return $authUser->can('ForceDelete:OrdenEstado');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrdenEstado');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrdenEstado');
    }

    public function replicate(AuthUser $authUser, OrdenEstado $ordenEstado): bool
    {
        return $authUser->can('Replicate:OrdenEstado');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrdenEstado');
    }
}
