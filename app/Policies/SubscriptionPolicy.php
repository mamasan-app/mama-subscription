<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        // El usuario debe tener el permiso 'view subscriptions'
        return $user->can('view subscriptions');
    }

    /**
     * Determine if the user can view the subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // El usuario debe tener el permiso 'view subscriptions'
        return $user->can('view subscriptions');
    }

    /**
     * Determine if the user can create subscriptions.
     */
    public function create(User $user): bool
    {
        // El usuario debe tener el permiso 'create subscriptions'
        return $user->can('create subscriptions');
    }

    /**
     * Determine if the user can update the subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // El usuario debe tener el permiso 'edit subscriptions'
        return $user->can('edit subscriptions');
    }

    /**
     * Determine if the user can delete the subscription.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        // El usuario debe tener el permiso 'delete subscriptions'
        return $user->can('delete subscriptions');
    }

    /**
     * Determine if the user can restore the subscription.
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        // El usuario debe tener el permiso 'restore subscriptions'
        return $user->can('restore subscriptions');
    }

    /**
     * Determine if the user can permanently delete the subscription.
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        // El usuario debe tener el permiso 'force delete subscriptions'
        return $user->can('force delete subscriptions');
    }
}
