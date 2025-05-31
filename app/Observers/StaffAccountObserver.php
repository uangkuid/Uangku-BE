<?php

namespace App\Observers;

use App\Models\StaffAccount;
use Illuminate\Support\Facades\Cache;

class StaffAccountObserver
{
    /**
     * Handle the StaffAccount "created" event.
     */
    public function created(StaffAccount $staffAccount): void
    {
        Cache::forget('staff_accounts:{$staffAccount->id}');
    }

    /**
     * Handle the StaffAccount "updated" event.
     */
    public function updated(StaffAccount $staffAccount): void
    {
        Cache::forget('staff_accounts:{$staffAccount->id}');
    }

    /**
     * Handle the StaffAccount "deleted" event.
     */
    public function deleted(StaffAccount $staffAccount): void
    {
        Cache::forget('staff_accounts:{$staffAccount->id}');
    }

    /**
     * Handle the StaffAccount "restored" event.
     */
    public function restored(StaffAccount $staffAccount): void
    {
        Cache::forget('staff_accounts:{$staffAccount->id}');
    }

    /**
     * Handle the StaffAccount "force deleted" event.
     */
    public function forceDeleted(StaffAccount $staffAccount): void
    {
        Cache::forget('staff_accounts:{$staffAccount->id}');
    }
}
