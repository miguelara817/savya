<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StockSaldo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class StockSaldoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StockSaldo');
    }

    public function view(AuthUser $authUser, StockSaldo $stockSaldo): bool
    {
        return $authUser->can('View:StockSaldo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StockSaldo');
    }

    public function update(AuthUser $authUser, StockSaldo $stockSaldo): bool
    {
        return $authUser->can('Update:StockSaldo');
    }

    public function delete(AuthUser $authUser, StockSaldo $stockSaldo): bool
    {
        return $authUser->can('Delete:StockSaldo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StockSaldo');
    }

    public function restore(AuthUser $authUser, StockSaldo $stockSaldo): bool
    {
        return $authUser->can('Restore:StockSaldo');
    }

    public function forceDelete(AuthUser $authUser, StockSaldo $stockSaldo): bool
    {
        return $authUser->can('ForceDelete:StockSaldo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StockSaldo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StockSaldo');
    }

    public function replicate(AuthUser $authUser, StockSaldo $stockSaldo): bool
    {
        return $authUser->can('Replicate:StockSaldo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StockSaldo');
    }
}
