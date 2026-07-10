<?php

namespace Database\Seeders;

use App\Models\StaffAccount;
use App\Services\Staff\StaffService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffService = app(StaffService::class);

        DB::transaction(function () use ($staffService) {
            $staff = $staffService->register(
                name: "Administrator",
                email: "admin@uangku.com",
                password: "Password123",
                isSeeder: true
            );

            $superAdmin = Role::findOrCreate('super_admin', 'web');
            $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());
            StaffAccount::find($staff['id'])->assignRole($superAdmin);
        });
    }
}
