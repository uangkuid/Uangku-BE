<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAccess;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WalletAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        $admin = User::where('name', 'Administrator')->first();
//        $wallet_name = sprintf("%s's Cash", $admin->name);
//        $wallet = Wallet::where('name', $wallet_name)->firstOrFail();
//
//        WalletAccess::create([
//            'users' => $admin->id,
//            'wallets' => $wallet->id,
//        ]);
    }
}
