<?php

namespace Database\Seeders;

use App\Models\CashFlow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CashFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CashFlow::create([
            'name' => 'Income'
        ]);
        CashFlow::create([
            'name' => 'Spending'
        ]);
    }
}
