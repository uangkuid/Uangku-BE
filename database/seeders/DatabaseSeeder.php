<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
