<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonaAutorizada;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PersonaAutorizadaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PersonaAutorizada');
    }

    public function view(AuthUser $authUser, PersonaAutorizada $personaAutorizada): bool
    {
        return $authUser->can('View:PersonaAutorizada');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PersonaAutorizada');
    }

    public function update(AuthUser $authUser, PersonaAutorizada $personaAutorizada): bool
    {
        return $authUser->can('Update:PersonaAutorizada');
    }

    public function delete(AuthUser $authUser, PersonaAutorizada $personaAutorizada): bool
    {
        return $authUser->can('Delete:PersonaAutorizada');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PersonaAutorizada');
    }

    public function restore(AuthUser $authUser, PersonaAutorizada $personaAutorizada): bool
    {
        return $authUser->can('Restore:PersonaAutorizada');
    }

    public function forceDelete(AuthUser $authUser, PersonaAutorizada $personaAutorizada): bool
    {
        return $authUser->can('ForceDelete:PersonaAutorizada');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PersonaAutorizada');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PersonaAutorizada');
    }

    public function replicate(AuthUser $authUser, PersonaAutorizada $personaAutorizada): bool
    {
        return $authUser->can('Replicate:PersonaAutorizada');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PersonaAutorizada');
    }
}
