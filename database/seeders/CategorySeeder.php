<?php

namespace Database\Seeders;

use App\Models\CashFlow;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $income = CashFlow::where('name', 'Income')->firstOrFail();
        $spending = CashFlow::where('name', 'Spending')->firstOrFail();

        Category::create([ 'name' => 'Salary', 'cash_flows' => $income->id ]);
        Category::create([ 'name' => 'Bonus', 'cash_flows' => $income->id ]);
        Category::create([ 'name' => 'Investment', 'cash_flows' => $income->id ]);
        Category::create([ 'name' => 'Other Income', 'cash_flows' => $income->id ]);

        Category::create([ 'name' => 'Beauty', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Bills', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Clothes', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Donation', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Education', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Entertainment', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Family', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Gasoline', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Health', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Insurance', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Investment', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Meals', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Others', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Pet', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Shopping', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Sport', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Technology', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Transportation', 'cash_flows' => $spending->id ]);
        Category::create([ 'name' => 'Vacation', 'cash_flows' => $spending->id ]);
    }
}
