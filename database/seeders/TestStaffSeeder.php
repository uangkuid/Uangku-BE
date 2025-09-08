<?php

namespace Database\Seeders;

use App\Models\StaffAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StaffAccount::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
    }
}