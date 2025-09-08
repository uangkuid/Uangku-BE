<?php

namespace App\Policies;

use App\Models\StaffAccount;
use App\Models\Transaction;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(StaffAccount $user): bool
    {
        return true; // Staff can view transactions
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(StaffAccount $user, Transaction $transaction): bool
    {
        return true; // Staff can view individual transactions
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(StaffAccount $user): bool
    {
        return false; // Staff cannot create transactions - read only
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(StaffAccount $user, Transaction $transaction): bool
    {
        return false; // Staff cannot update transactions - read only
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(StaffAccount $user, Transaction $transaction): bool
    {
        return false; // Staff cannot delete transactions - read only
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(StaffAccount $user, Transaction $transaction): bool
    {
        return false; // Staff cannot restore transactions - read only
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(StaffAccount $user, Transaction $transaction): bool
    {
        return false; // Staff cannot force delete transactions - read only
    }
}