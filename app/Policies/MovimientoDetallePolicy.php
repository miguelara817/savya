<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MovimientoDetalle;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MovimientoDetallePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MovimientoDetalle');
    }

    public function view(AuthUser $authUser, MovimientoDetalle $movimientoDetalle): bool
    {
        return $authUser->can('View:MovimientoDetalle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MovimientoDetalle');
    }

    public function update(AuthUser $authUser, MovimientoDetalle $movimientoDetalle): bool
    {
        return $authUser->can('Update:MovimientoDetalle');
    }

    public function delete(AuthUser $authUser, MovimientoDetalle $movimientoDetalle): bool
    {
        return $authUser->can('Delete:MovimientoDetalle');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MovimientoDetalle');
    }

    public function restore(AuthUser $authUser, MovimientoDetalle $movimientoDetalle): bool
    {
        return $authUser->can('Restore:MovimientoDetalle');
    }

    public function forceDelete(AuthUser $authUser, MovimientoDetalle $movimientoDetalle): bool
    {
        return $authUser->can('ForceDelete:MovimientoDetalle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MovimientoDetalle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MovimientoDetalle');
    }

    public function replicate(AuthUser $authUser, MovimientoDetalle $movimientoDetalle): bool
    {
        return $authUser->can('Replicate:MovimientoDetalle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MovimientoDetalle');
    }
}
