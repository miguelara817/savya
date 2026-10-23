<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrdenPersona;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrdenPersonaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrdenPersona');
    }

    public function view(AuthUser $authUser, OrdenPersona $ordenPersona): bool
    {
        return $authUser->can('View:OrdenPersona');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrdenPersona');
    }

    public function update(AuthUser $authUser, OrdenPersona $ordenPersona): bool
    {
        return $authUser->can('Update:OrdenPersona')
            && (bool) $ordenPersona->orden?->esEditable();
    }

    public function delete(AuthUser $authUser, OrdenPersona $ordenPersona): bool
    {
        return $authUser->can('Delete:OrdenPersona')
            && (bool) $ordenPersona->orden?->esEditable();
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrdenPersona');
    }

    public function restore(AuthUser $authUser, OrdenPersona $ordenPersona): bool
    {
        return $authUser->can('Restore:OrdenPersona');
    }

    public function forceDelete(AuthUser $authUser, OrdenPersona $ordenPersona): bool
    {
        return $authUser->can('ForceDelete:OrdenPersona')
            && (bool) $ordenPersona->orden?->esEditable();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrdenPersona');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrdenPersona');
    }

    public function replicate(AuthUser $authUser, OrdenPersona $ordenPersona): bool
    {
        return $authUser->can('Replicate:OrdenPersona');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrdenPersona');
    }
}
