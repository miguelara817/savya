<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UserSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserSessionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserSession');
    }

    public function view(AuthUser $authUser, UserSession $userSession): bool
    {
        return $authUser->can('View:UserSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UserSession');
    }

    public function update(AuthUser $authUser, UserSession $userSession): bool
    {
        return $authUser->can('Update:UserSession');
    }

    public function delete(AuthUser $authUser, UserSession $userSession): bool
    {
        return $authUser->can('Delete:UserSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UserSession');
    }

    public function restore(AuthUser $authUser, UserSession $userSession): bool
    {
        return $authUser->can('Restore:UserSession');
    }

    public function forceDelete(AuthUser $authUser, UserSession $userSession): bool
    {
        return $authUser->can('ForceDelete:UserSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UserSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UserSession');
    }

    public function replicate(AuthUser $authUser, UserSession $userSession): bool
    {
        return $authUser->can('Replicate:UserSession');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UserSession');
    }

}