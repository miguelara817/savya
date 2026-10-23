<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Entrega;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EntregaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Entrega');
    }

    public function view(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('View:Entrega');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Entrega');
    }

    public function update(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('Update:Entrega')
            && $entrega->estado === Entrega::ESTADO_BORRADOR;
    }

    public function delete(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('Delete:Entrega')
            && $entrega->estado === Entrega::ESTADO_BORRADOR;
    }

    public function confirmar(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('Confirmar:Entrega')
            && $entrega->estado === Entrega::ESTADO_BORRADOR;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Entrega');
    }

    public function restore(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('Restore:Entrega');
    }

    public function forceDelete(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('ForceDelete:Entrega')
            && $entrega->estado === Entrega::ESTADO_BORRADOR;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Entrega');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Entrega');
    }

    public function replicate(AuthUser $authUser, Entrega $entrega): bool
    {
        return $authUser->can('Replicate:Entrega');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Entrega');
    }
}
