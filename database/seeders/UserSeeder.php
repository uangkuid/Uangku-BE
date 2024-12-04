<?php

namespace Database\Seeders;

use App\Enums\RoleWallet;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAccess;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws Exception
     */
    public function run(): void
    {
        DB::transaction(function () {
            $secretKey = env('ADMIN_SECRET_KEY');
            $salt = EncryptionHelper::getUsersSalt($secretKey);
            $secretKeySanitize = str_replace("-", "", $secretKey);
            $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
            $password = "Password123";
            $encryptKey = EncryptionHelper::getUsersEncryptKey($secretKey, $password);

            /**
             * Create Account
             */
            $user = User::create([
                'name' => EncryptionHelper::encryptAsString(
                    data: 'Administrator',
                    key: EncryptionHelper::getSystemSecretKey(),
                    iv: $staticIv,
                ),
                'email' => EncryptionHelper::encryptAsString(
                    data: 'admin@uangku.com',
                    key: EncryptionHelper::getSystemSecretKey(),
                    iv: $staticIv,
                ),
                'password' => bcrypt($encryptKey),
                'email_verified_at' => now(),
            ]);

            $wallet_name = sprintf("%s's Cash", 'Administrator');

            /**
             * Create users wallet
             */
            $wallet = Wallet::create([
                'name' => EncryptionHelper::encryptAsString(
                    data: $wallet_name,
                    key: $encryptKey,
                    iv: $staticIv,
                ),
                'amount' => EncryptionHelper::encryptAsString(
                    data: "0",
                    key: $encryptKey,
                    iv: $staticIv,
                ),
                'created_by' => $user->id,
            ]);

            /**
             * Grant users access to wallet
             */
            $walletAccess = WalletAccess::create([
                'users' => $user->id,
                'wallets' => $wallet->id,
                'is_active' => true,
                'role' => RoleWallet::Admin
            ]);
        });
    }
}
