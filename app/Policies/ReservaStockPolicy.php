<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReservaStock;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservaStockPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReservaStock');
    }

    public function view(AuthUser $authUser, ReservaStock $reservaStock): bool
    {
        return $authUser->can('View:ReservaStock');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function update(AuthUser $authUser, ReservaStock $reservaStock): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, ReservaStock $reservaStock): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, ReservaStock $reservaStock): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, ReservaStock $reservaStock): bool
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

    public function replicate(AuthUser $authUser, ReservaStock $reservaStock): bool
    {
        return $authUser->can('Replicate:ReservaStock');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReservaStock');
    }
}
