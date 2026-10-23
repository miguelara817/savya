<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrdenProducto;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrdenProductoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrdenProducto');
    }

    public function view(AuthUser $authUser, OrdenProducto $ordenProducto): bool
    {
        return $authUser->can('View:OrdenProducto');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrdenProducto');
    }

    public function update(AuthUser $authUser, OrdenProducto $ordenProducto): bool
    {
        return $authUser->can('Update:OrdenProducto')
            && (bool) $ordenProducto->orden?->esEditable();
    }

    public function delete(AuthUser $authUser, OrdenProducto $ordenProducto): bool
    {
        return $authUser->can('Delete:OrdenProducto')
            && (bool) $ordenProducto->orden?->esEditable();
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrdenProducto');
    }

    public function restore(AuthUser $authUser, OrdenProducto $ordenProducto): bool
    {
        return $authUser->can('Restore:OrdenProducto');
    }

    public function forceDelete(AuthUser $authUser, OrdenProducto $ordenProducto): bool
    {
        return $authUser->can('ForceDelete:OrdenProducto')
            && (bool) $ordenProducto->orden?->esEditable();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrdenProducto');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrdenProducto');
    }

    public function replicate(AuthUser $authUser, OrdenProducto $ordenProducto): bool
    {
        return $authUser->can('Replicate:OrdenProducto');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrdenProducto');
    }
}
