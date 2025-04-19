<?php

namespace Database\Seeders;

use App\Enums\RoleWallet;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAccess;
use App\Services\Auth\AuthService;
use App\Services\UserConfig\UserConfigService;
use App\Services\UserSession\UserSessionService;
use App\Services\Wallet\WalletService;
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
        $authService = app(AuthService::class);
        $walletService = app(WalletService::class);
        $userSessionService = app(UserSessionService::class);
        $userConfigService = app(UserConfigService::class);
        DB::transaction(function () use ($userConfigService, $authService, $walletService, $userSessionService) {
            $password = "Password123";
            /**
             * Create Account
             */
            $registerResult = $authService->register(
                name: "Administrator",
                email: "admin@uangku.com",
                password: $password,
                otp: "000000",
                uuid: "00000000-0000-0000-0000-000000000000",
                isSeeder: true,
            );
            $user = $registerResult['user'];
//            $user = User::create([
//                'name' => EncryptionHelper::encryptAsString(
//                    data: 'Administrator',
//                    key: EncryptionHelper::getSystemSecretKey(),
//                    iv: $staticIv,
//                ),
//                'email' => EncryptionHelper::encryptAsString(
//                    data: 'admin@uangku.com',
//                    key: EncryptionHelper::getSystemSecretKey(),
//                    iv: $staticIv,
//                ),
//                'password' => bcrypt($encryptKey),
//                'email_verified_at' => now(),
//            ]);
            /**
             * Save User Key
             */
            $userKey = $authService->saveUserKey(
                userId: $user->id,
                publicKey: $registerResult['public_key'],
                privateKey: $registerResult['private_key'],
                secretKey: $registerResult['secret_key'],
                password: $password
            );

            $config = $userConfigService->create([
                'users' => $user->id,
                'is_pin_enabled' => false,
                'start_date_month' => EncryptionHelper::encryptAsymmetric("1", $registerResult['raw_public_key'])
            ]);

            $wallet_name = sprintf("%s's Cash", 'Administrator');

            /**
             * Create users wallet
             */
            $wallet = $walletService->create([
                'name' => EncryptionHelper::encryptAsymmetric($wallet_name, $registerResult['raw_public_key']),
                'amount' => EncryptionHelper::encryptAsymmetric("0", $registerResult['raw_public_key']),
                'created_by' => $user->id,
            ]);
//            $wallet = Wallet::create([
//                'name' => EncryptionHelper::encryptAsString(
//                    data: $wallet_name,
//                    key: $encryptKey,
//                    iv: $staticIv,
//                ),
//                'amount' => EncryptionHelper::encryptAsString(
//                    data: "0",
//                    key: $encryptKey,
//                    iv: $staticIv,
//                ),
//                'created_by' => $user->id,
//            ]);

            /**
             * Grant users access to wallet
             */
            $walletAccess = $walletService->grantAccess(
                userId: $user->id,
                walletId: $wallet->id,
                accessType: RoleWallet::Admin
            );
        });
    }
}
