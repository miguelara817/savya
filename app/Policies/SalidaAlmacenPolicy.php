<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SalidaAlmacen;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SalidaAlmacenPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalidaAlmacen');
    }

    public function view(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('View:SalidaAlmacen');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalidaAlmacen');
    }

    public function update(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('Update:SalidaAlmacen')
            && $salidaAlmacen->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function delete(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('Delete:SalidaAlmacen')
            && $salidaAlmacen->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function confirmar(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('Confirmar:SalidaAlmacen')
            && $salidaAlmacen->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SalidaAlmacen');
    }

    public function restore(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('Restore:SalidaAlmacen');
    }

    public function forceDelete(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('ForceDelete:SalidaAlmacen')
            && $salidaAlmacen->estado === SalidaAlmacen::ESTADO_BORRADOR;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalidaAlmacen');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalidaAlmacen');
    }

    public function replicate(AuthUser $authUser, SalidaAlmacen $salidaAlmacen): bool
    {
        return $authUser->can('Replicate:SalidaAlmacen');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalidaAlmacen');
    }
}
