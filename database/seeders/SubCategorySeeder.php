<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bonus = Category::where('name', 'Bonus')->first();
        $admin = User::where('name', 'Administrator')->first();

        if($bonus == null || $admin == null) {
            return;
        }

        SubCategory::create([
            'categories' => $bonus->id,
            'users' => $admin->id,
            'name' => 'Holiday allowance',
        ]);

        SubCategory::create([
            'categories' => $bonus->id,
            'users' => $admin->id,
            'name' => 'Yearly allowance',
        ]);

        SubCategory::create([
            'categories' => $bonus->id,
            'users' => $admin->id,
            'name' => 'Performance allowance',
        ]);
    }
}
