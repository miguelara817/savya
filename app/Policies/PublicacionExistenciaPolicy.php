<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PublicacionExistencia;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PublicacionExistenciaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PublicacionExistencia');
    }

    public function view(AuthUser $authUser, PublicacionExistencia $publicacionExistencia): bool
    {
        return $authUser->can('View:PublicacionExistencia');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PublicacionExistencia');
    }

    public function update(AuthUser $authUser, PublicacionExistencia $publicacionExistencia): bool
    {
        return $authUser->can('Update:PublicacionExistencia');
    }

    public function delete(AuthUser $authUser, PublicacionExistencia $publicacionExistencia): bool
    {
        return $authUser->can('Delete:PublicacionExistencia');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PublicacionExistencia');
    }

    public function restore(AuthUser $authUser, PublicacionExistencia $publicacionExistencia): bool
    {
        return $authUser->can('Restore:PublicacionExistencia');
    }

    public function forceDelete(AuthUser $authUser, PublicacionExistencia $publicacionExistencia): bool
    {
        return $authUser->can('ForceDelete:PublicacionExistencia');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PublicacionExistencia');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PublicacionExistencia');
    }

    public function replicate(AuthUser $authUser, PublicacionExistencia $publicacionExistencia): bool
    {
        return $authUser->can('Replicate:PublicacionExistencia');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PublicacionExistencia');
    }
}
