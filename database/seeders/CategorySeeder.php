<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TransactionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $income = TransactionType::where('name', 'Income')->firstOrFail();
        $spending = TransactionType::where('name', 'Spending')->firstOrFail();

        DB::transaction(function () use ($income, $spending) {
            Category::create([ 'name' => 'Salary', 'transaction_types' => $income->id , 'icon' => 'Salary.png']);
            Category::create([ 'name' => 'Bonus', 'transaction_types' => $income->id, 'icon' => 'Bonus.png']);
            Category::create([ 'name' => 'Investment', 'transaction_types' => $income->id, 'icon' => 'Investment.png']);
            Category::create([ 'name' => 'Other Income', 'transaction_types' => $income->id, 'icon' => 'OtherIncome.png']);

            Category::create([ 'name' => 'Beauty', 'transaction_types' => $spending->id, 'icon' => 'Beauty.png']);
            Category::create([ 'name' => 'Bills', 'transaction_types' => $spending->id, 'icon' => 'Bills.png']);
            Category::create([ 'name' => 'Clothes', 'transaction_types' => $spending->id, 'icon' => 'Clothes.png']);
            Category::create([ 'name' => 'Donation', 'transaction_types' => $spending->id, 'icon' => 'Donation.png']);
            Category::create([ 'name' => 'Education', 'transaction_types' => $spending->id, 'icon' => 'Education.png']);
            Category::create([ 'name' => 'Entertainment', 'transaction_types' => $spending->id, 'icon' => 'Entertainment.png']);
            Category::create([ 'name' => 'Family', 'transaction_types' => $spending->id, 'icon' => 'Family.png']);
            Category::create([ 'name' => 'Food', 'transaction_types' => $spending->id, 'icon' => 'Food.png']);
            Category::create([ 'name' => 'Gasoline', 'transaction_types' => $spending->id, 'icon' => 'Gasoline.png']);
            Category::create([ 'name' => 'Health', 'transaction_types' => $spending->id, 'icon' => 'Health.png']);
            Category::create([ 'name' => 'Insurance', 'transaction_types' => $spending->id, 'icon' => 'Insurance.png']);
            Category::create([ 'name' => 'Investment', 'transaction_types' => $spending->id, 'icon' => 'Investment.png']);
            Category::create([ 'name' => 'Meals', 'transaction_types' => $spending->id, 'icon' => 'Meals.png']);
            Category::create([ 'name' => 'Others', 'transaction_types' => $spending->id, 'icon' => 'Others.png']);
            Category::create([ 'name' => 'Pet', 'transaction_types' => $spending->id, 'icon' => 'Pet.png']);
            Category::create([ 'name' => 'Shopping', 'transaction_types' => $spending->id, 'icon' => 'Shopping.png']);
            Category::create([ 'name' => 'Sport', 'transaction_types' => $spending->id, 'icon' => 'Sport.png']);
            Category::create([ 'name' => 'Technology', 'transaction_types' => $spending->id, 'icon' => 'Technology.png']);
            Category::create([ 'name' => 'Transportation', 'transaction_types' => $spending->id, 'icon' => 'Transportation.png']);
            Category::create([ 'name' => 'Telecommunication', 'transaction_types' => $spending->id, 'icon' => 'Telecommunication.png']);
            Category::create([ 'name' => 'Vacation', 'transaction_types' => $spending->id, 'icon' => 'Vacation.png']);
        });
    }
}
