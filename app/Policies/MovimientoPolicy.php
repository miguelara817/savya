<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Movimiento;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MovimientoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Movimiento');
    }

    public function view(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('View:Movimiento');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $movimiento->estado !== Movimiento::ESTADO_CONFIRMADO
            && $authUser->can('Update:Movimiento');
    }

    public function delete(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $movimiento->estado !== Movimiento::ESTADO_CONFIRMADO
            && $authUser->can('Delete:Movimiento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, Movimiento $movimiento): bool
    {
        return $authUser->can('Replicate:Movimiento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Movimiento');
    }
}
