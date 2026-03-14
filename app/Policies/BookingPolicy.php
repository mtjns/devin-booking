<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Intercepts all authorization checks for bookings.
     * Instantly approves the action if the user holds the super administrator flag.
     * Returning null allows the specific methods below to evaluate the permissions.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return null;
    }

    /**
     * Determines if the user is allowed to see the list or calendar of bookings.
     */
    public function viewAny(User $user): bool
    {
        return $user->can_view_bookings;
    }

    /**
     * Determines if the user is allowed to open and view the details of a single booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->can_view_bookings;
    }

    /**
     * Determines if the user is allowed to manually create a new booking from the dashboard.
     */
    public function create(User $user): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to modify dates, guest counts, or statuses of an existing booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to permanently remove a single booking record from the database.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to delete multiple booking records at once via bulk actions.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to recover a soft-deleted booking record.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to recover multiple soft-deleted booking records at once.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to permanently destroy a soft-deleted booking record.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return $user->can_edit_bookings;
    }

    /**
     * Determines if the user is allowed to permanently destroy multiple soft-deleted booking records at once.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can_edit_bookings;
    }
}