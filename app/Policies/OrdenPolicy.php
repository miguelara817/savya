<?php

declare(strict_types=1);

namespace App\Policies;

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
        return $authUser->can('View:Orden');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Orden');
    }

    public function update(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Update:Orden');
    }

    public function delete(AuthUser $authUser, Orden $orden): bool
    {
        return $authUser->can('Delete:Orden');
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
        return $authUser->can('ForceDelete:Orden');
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
