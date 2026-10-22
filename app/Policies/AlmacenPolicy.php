<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Almacen;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AlmacenPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Almacen');
    }

    public function view(AuthUser $authUser, Almacen $almacen): bool
    {
        return $authUser->can('View:Almacen');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Almacen');
    }

    public function update(AuthUser $authUser, Almacen $almacen): bool
    {
        return $authUser->can('Update:Almacen');
    }

    public function delete(AuthUser $authUser, Almacen $almacen): bool
    {
        return $authUser->can('Delete:Almacen');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Almacen');
    }

    public function restore(AuthUser $authUser, Almacen $almacen): bool
    {
        return $authUser->can('Restore:Almacen');
    }

    public function forceDelete(AuthUser $authUser, Almacen $almacen): bool
    {
        return $authUser->can('ForceDelete:Almacen');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Almacen');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Almacen');
    }

    public function replicate(AuthUser $authUser, Almacen $almacen): bool
    {
        return $authUser->can('Replicate:Almacen');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Almacen');
    }
}
