<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        //        User::factory()->create([
        //            'name' => 'Test User',
        //            'email' => 'test@example.com',
        //        ]);

        // Shield permissions are normally created by manually running `shield:generate`,
        // which isn't wired into migrate/seed. Run it here so a fresh DB always has the
        // Permission rows that policies/roles rely on (also auto-grants them to super_admin,
        // since config('filament-shield.super_admin.define_via_gate') is false).
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'staffsus',
            '--option' => 'permissions',
        ]);

        $this->call([
            UserSeeder::class,
            TransactionTypeSeeder::class,
            CategorySeeder::class,
            SubCategorySeeder::class,
            WalletSeeder::class,
            WalletAccessSeeder::class,
            StaffAccountSeeder::class,
            ShieldRoleSeeder::class,
        ]);
    }
}
