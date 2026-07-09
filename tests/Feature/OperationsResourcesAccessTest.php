<?php

namespace Tests\Feature;

use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Epic 2: TransactionResource/WalletResource/FamilyResource harus digate oleh
 * permission Shield (guard 'web'), bukan terbuka untuk semua staff.
 */
class OperationsResourcesAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffWithPermissions(array $permissions): StaffAccount
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Test',
            'email' => 'staff-'.uniqid().'@uangku.test',
            'password' => bcrypt('password'),
        ]);

        if ($permissions !== []) {
            $role = Role::findOrCreate('test-role-'.uniqid(), 'web');
            foreach ($permissions as $permissionName) {
                $permission = Permission::findOrCreate($permissionName, 'web');
                $role->givePermissionTo($permission);
            }
            $staff->assignRole($role);
        }

        return $staff;
    }

    public function test_staff_with_permission_can_access_operations_resources(): void
    {
        $staff = $this->makeStaffWithPermissions([
            'ViewAny:Transaction',
            'ViewAny:Wallet',
            'ViewAny:Family',
        ]);

        $this->actingAs($staff, 'web');

        $this->get('/staffsus/transactions')->assertOk();
        $this->get('/staffsus/wallets')->assertOk();
        $this->get('/staffsus/families')->assertOk();
    }

    public function test_staff_without_permission_is_forbidden(): void
    {
        $staff = $this->makeStaffWithPermissions([
            // Punya role (supaya lolos canAccessPanel) tapi tanpa permission Transaction/Wallet/Family.
            'ViewAny:User',
        ]);

        $this->actingAs($staff, 'web');

        $this->get('/staffsus/transactions')->assertForbidden();
        $this->get('/staffsus/wallets')->assertForbidden();
        $this->get('/staffsus/families')->assertForbidden();
    }
}
