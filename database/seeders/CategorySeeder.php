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
            Category::create([ 'name' => 'Salary', 'transaction_types' => $income->id ]);
            Category::create([ 'name' => 'Bonus', 'transaction_types' => $income->id ]);
            Category::create([ 'name' => 'Investment', 'transaction_types' => $income->id ]);
            Category::create([ 'name' => 'Other Income', 'transaction_types' => $income->id ]);

            Category::create([ 'name' => 'Beauty', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Bills', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Clothes', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Donation', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Education', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Entertainment', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Family', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Gasoline', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Health', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Insurance', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Investment', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Meals', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Others', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Pet', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Shopping', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Sport', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Technology', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Transportation', 'transaction_types' => $spending->id ]);
            Category::create([ 'name' => 'Vacation', 'transaction_types' => $spending->id ]);
        });
    }
}
