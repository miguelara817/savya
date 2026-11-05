<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceDataInfo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ServiceDataInfoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ServiceDataInfo');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(AuthUser $authUser, ServiceDataInfo $serviceDataInfo): bool
    {
        return $authUser->can('View:ServiceDataInfo');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ServiceDataInfo');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(AuthUser $authUser, ServiceDataInfo $serviceDataInfo): bool
    {
        return $authUser->can('Update:ServiceDataInfo');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(AuthUser $authUser, ServiceDataInfo $serviceDataInfo): bool
    {
        return $authUser->can('Delete:ServiceDataInfo');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(AuthUser $authUser, ServiceDataInfo $serviceDataInfo): bool
    {
        return $authUser->can('Restore:ServiceDataInfo');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(AuthUser $authUser, ServiceDataInfo $serviceDataInfo): bool
    {
        return $authUser->can('ForceDelete:ServiceDataInfo');
    }
}
