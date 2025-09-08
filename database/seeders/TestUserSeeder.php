<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users for current month with various dates
        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        
        $testData = [
            // Early month
            [$startOfMonth->copy()->addDays(1), 3],
            [$startOfMonth->copy()->addDays(2), 2],
            [$startOfMonth->copy()->addDays(5), 5],
            
            // Mid month
            [$startOfMonth->copy()->addDays(10), 4],
            [$startOfMonth->copy()->addDays(15), 6],
            [$startOfMonth->copy()->addDays(18), 2],
            
            // Recent days (if not too far)
            [$today->copy()->subDays(3), 3],
            [$today->copy()->subDays(1), 4],
        ];
        
        $counter = 1;
        foreach ($testData as [$date, $count]) {
            // Only create users for dates not in the future
            if ($date->lte(Carbon::now())) {
                for ($i = 0; $i < $count; $i++) {
                    User::create([
                        'name' => "Test User {$counter}",
                        'email' => "testuser{$counter}@example.com",
                        'password' => Hash::make('password'),
                        'email_verified_at' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                    $counter++;
                }
            }
        }
    }
}