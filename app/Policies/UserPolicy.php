<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Intercepts all authorization checks.
     * Grants absolute access if the user is marked as a super administrator.
     * Returning null allows the subsequent specific policy methods to run.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return null;
    }

    /**
     * Determines if the user can view the user list in the administration sidebar.
     */
    public function viewAny(User $user): bool
    {
        return $user->can_manage_users;
    }

    /**
     * Determines if the user can view a specific user's details.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can_manage_users;
    }

    /**
     * Determines if the user can access the creation form for new staff accounts.
     */
    public function create(User $user): bool
    {
        return $user->can_manage_users;
    }

    /**
     * Determines if the user can modify existing staff accounts.
     */
    public function update(User $user, User $model): bool
    {
        if ($model->is_super_admin && ! $user->is_super_admin) {
            return false;
        }

        return $user->can_manage_users;
    }

    /**
     * Determines if the user can permanently delete a single staff account.
     */
    public function delete(User $user, User $model): bool
    {
        if ($model->is_super_admin && ! $user->is_super_admin) {
            return false;
        }

        return $user->can_manage_users;
    }

    /**
     * Determines if the user can permanently delete multiple staff accounts at once.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can_manage_users;
    }

    /**
     * Determines if the user can recover a soft-deleted staff account.
     */
    public function restore(User $user, User $model): bool
    {
        if ($model->is_super_admin && ! $user->is_super_admin) {
            return false;
        }

        return $user->can_manage_users;
    }

    /**
     * Determines if the user can recover multiple soft-deleted staff accounts at once.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can_manage_users;
    }

    /**
     * Determines if the user can permanently destroy a soft-deleted staff account.
     */
    public function forceDelete(User $user, User $model): bool
    {
        if ($model->is_super_admin && ! $user->is_super_admin) {
            return false;
        }

        return $user->can_manage_users;
    }

    /**
     * Determines if the user can permanently destroy multiple soft-deleted staff accounts at once.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can_manage_users;
    }
}