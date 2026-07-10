<?php

use App\Models\StaffAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Safety net before dropping `staff_accounts.role`: any account that still relies on the
     * legacy `role = 'admin'` fallback in StaffAccount::canAccessPanel() gets the Shield
     * `super_admin` role so it doesn't lose panel access once the column is gone.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('staff_accounts', 'role')) {
            return;
        }

        $legacyAdminIds = DB::table('staff_accounts')
            ->where('role', 'admin')
            ->pluck('id');

        if ($legacyAdminIds->isEmpty()) {
            return;
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');

        foreach ($legacyAdminIds as $id) {
            $staff = StaffAccount::find($id);

            if ($staff && ! $staff->roles()->exists()) {
                $staff->assignRole($superAdmin);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill only; not reversible (would require tracking which roles this
        // migration itself assigned, vs. ones already present).
    }
};
