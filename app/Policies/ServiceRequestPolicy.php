<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return match ($user->role) {
            Role::Employee => $this->isOwner($user, $serviceRequest),
            Role::Manager => $this->isManagerOf($user, $serviceRequest)
                && $serviceRequest->status !== RequestStatus::Draft,
            Role::Operations => $serviceRequest->status->isVisibleToOperations(),
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isEmployee();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isEmployee() && $this->isOwner($user, $serviceRequest);
    }

    public function submit(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->update($user, $serviceRequest);
    }

    public function decide(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isManager() && $this->isManagerOf($user, $serviceRequest);
    }

    public function assign(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isOperations();
    }

    public function changeStatus(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isOperations();
    }

    public function comment(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->view($user, $serviceRequest); // participants = people who can see it
    }

    private function isOwner(User $user, ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->requester_id === $user->id;
    }

    private function isManagerOf(User $user, ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->requester->manager_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return false;
    }
}
