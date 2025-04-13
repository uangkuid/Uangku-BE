<?php

namespace Database\Seeders;

use App\Enums\RoleWallet;
use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAccess;
use App\Services\Auth\AuthService;
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
        DB::transaction(function () use ($authService, $walletService, $userSessionService) {
            $secretKey = env('ADMIN_SECRET_KEY');
            $staticIv = env("MAIN_STATIC_IV") ?? throw new Exception("Static IV not found!");
            $password = "Password123";
            $asymmetricKey = EncryptionHelper::generateAsymmetricKey();
            $rawPublicKey = base64_decode($asymmetricKey["public"]);
            $encryptKey = EncryptionHelper::getUsersEncryptKey($secretKey, $password);

            /**
             * Create Account
             */
            $user = $authService->register(
                name: EncryptionHelper::encryptAsymmetric('Administrator', $rawPublicKey),
                email: EncryptionHelper::encryptAsString(
                    data: "admin@uangku.com",
                    key: EncryptionHelper::getSystemSecretKey(),
                    iv: $staticIv,
                ),
                password: $password,
            );
            $user->update([
                'email_verified_at' => now(),
            ]);
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
                publicKey: $asymmetricKey['public'],
                privateKey: $asymmetricKey['private'],
                secretKey: $secretKey,
                password: $password
            );

            $wallet_name = sprintf("%s's Cash", 'Administrator');

            /**
             * Create users wallet
             */
            $wallet = $walletService->create([
                'name' => EncryptionHelper::encryptAsymmetric($wallet_name, $rawPublicKey),
                'amount' => EncryptionHelper::encryptAsymmetric("0", $rawPublicKey),
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
