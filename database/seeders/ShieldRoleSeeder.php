<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed role per-tim untuk panel admin (guard 'web' / StaffAccount).
 *
 * super_admin dikelola Shield (akses penuh via gate) dan tidak diseed di sini.
 * Permission yang belum ada (mis. resource Epic 1/2 yang belum dibuat) diabaikan
 * secara aman, jadi seeder ini idempotent & bisa dijalankan ulang setelah
 * `php artisan shield:generate` menambah permission baru.
 */
class ShieldRoleSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = [
            // CS: lihat & kelola lifecycle user.
            'support' => [
                'ViewAny:User', 'View:User',
                'User:Suspend', 'User:Ban', 'User:VerifyEmail', 'User:ResetPin', 'User:ForceLogout',
            ],
            // Finance/Ops: lihat user.
            'finance' => [
                'ViewAny:User', 'View:User',
            ],
            // Marketing/Product: kelola feature flag & kategori, lihat metrik.
            'marketing' => [
                'ViewAny:User', 'View:User',
                'ViewAny:FeatureStatus', 'View:FeatureStatus', 'Create:FeatureStatus', 'Update:FeatureStatus', 'Delete:FeatureStatus', 'DeleteAny:FeatureStatus',
                'ViewAny:Category', 'View:Category', 'Create:Category', 'Update:Category', 'Delete:Category', 'DeleteAny:Category',
                'View:UserRegistrationsChart',
            ],
            // Engineering/DevOps: kelola konfigurasi sistem & feature flag.
            'engineering' => [
                'ViewAny:SystemConfig', 'View:SystemConfig', 'Create:SystemConfig', 'Update:SystemConfig', 'Delete:SystemConfig', 'DeleteAny:SystemConfig',
                'ViewAny:FeatureStatus', 'View:FeatureStatus', 'Create:FeatureStatus', 'Update:FeatureStatus', 'Delete:FeatureStatus', 'DeleteAny:FeatureStatus',
                'ViewAny:AuditLog', 'View:AuditLog',
                'View:UserRegistrationsChart',
            ],
        ];

        foreach ($matrix as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, self::GUARD);

            // Hanya sinkronkan permission yang benar-benar ada agar aman.
            $existing = Permission::where('guard_name', self::GUARD)
                ->whereIn('name', $permissionNames)
                ->pluck('name')
                ->all();

            $role->syncPermissions($existing);

            $this->command?->info("Role [{$roleName}] disinkronkan dengan ".count($existing).' permission.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
