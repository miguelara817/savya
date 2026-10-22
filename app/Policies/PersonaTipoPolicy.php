<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonaTipo;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PersonaTipoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PersonaTipo');
    }

    public function view(AuthUser $authUser, PersonaTipo $personaTipo): bool
    {
        return $authUser->can('View:PersonaTipo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PersonaTipo');
    }

    public function update(AuthUser $authUser, PersonaTipo $personaTipo): bool
    {
        return $authUser->can('Update:PersonaTipo');
    }

    public function delete(AuthUser $authUser, PersonaTipo $personaTipo): bool
    {
        return $authUser->can('Delete:PersonaTipo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PersonaTipo');
    }

    public function restore(AuthUser $authUser, PersonaTipo $personaTipo): bool
    {
        return $authUser->can('Restore:PersonaTipo');
    }

    public function forceDelete(AuthUser $authUser, PersonaTipo $personaTipo): bool
    {
        return $authUser->can('ForceDelete:PersonaTipo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PersonaTipo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PersonaTipo');
    }

    public function replicate(AuthUser $authUser, PersonaTipo $personaTipo): bool
    {
        return $authUser->can('Replicate:PersonaTipo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PersonaTipo');
    }
}
