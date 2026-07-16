<?php

namespace Database\Seeders;

use App\Enums\RoleWallet;
use App\Helpers\EncryptionHelper;
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
     *
     * The real registration flow is Zero-Knowledge: the client generates the
     * secret key, the RSA keypair, and wraps the private key before ever
     * talking to the server. A seeder has no client, so this simulates that
     * client-side work in PHP with fixed, well-known seed credentials
     * (never do this for a real user).
     *
     * @throws Exception
     */
    public function run(): void
    {
        $authService = app(AuthService::class);
        $walletService = app(WalletService::class);
        $userSessionService = app(UserSessionService::class);
        $userConfigService = app(UserConfigService::class);

        DB::transaction(function () use ($userConfigService, $authService, $walletService, $userSessionService) {
            $password = 'Password123';
            $secretKey = env('ADMIN_SECRET_KEY', 'UANGKU-SEEDED-ADMIN0-00000-00000-00000');
            $salt = random_bytes(16);

            // --- Simulated client-side 2SKD + keygen (see docs/encryption.md) ---
            $stretched = EncryptionHelper::pbkdf2($password, $salt, EncryptionHelper::PBKDF2_ITERATIONS, 32);
            $kdfSecret = EncryptionHelper::hkdf($secretKey, 'uangku-secretkey-v1', 32, 'admin@uangku.com');
            $unlockKey = $stretched ^ $kdfSecret;
            $authKey = base64_encode(EncryptionHelper::hkdf($unlockKey, 'uangku-auth-v1', 32));

            $keyPair = openssl_pkey_new([
                'digest_alg' => 'sha256',
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            openssl_pkey_export($keyPair, $privateKeyPem);
            $publicKeyPem = openssl_pkey_get_details($keyPair)['key'];

            $wrappedPrivateKey = EncryptionHelper::aesGcmEncrypt($privateKeyPem, $unlockKey);
            // --- end simulated client-side work ---

            $registerResult = $authService->register(
                name: 'Administrator',
                email: 'admin@uangku.com',
                authKey: $authKey,
                salt: base64_encode($salt),
                publicKey: base64_encode($publicKeyPem),
                wrappedPrivateKey: $wrappedPrivateKey,
                otp: '000000',
                uuid: '00000000-0000-0000-0000-000000000000',
                isSeeder: true,
            );

            $user = $registerResult['user'];

            $userConfigService->create([
                'users' => $user->id,
                'is_pin_enabled' => false,
                'start_date_month' => null,
            ]);

            $wallet = $walletService->create([
                // Client-encrypted values in the real flow; plaintext here since
                // the seed admin account has no real client to decrypt them.
                'name' => "Administrator's Cash",
                'amount' => '0',
                'created_by' => $user->id,
            ]);

            $walletService->grantAccess(
                userId: $user->id,
                walletId: $wallet->id,
                accessType: RoleWallet::Admin
            );

            $userSessionService->create([
                'refresh_token' => $registerResult['refresh_token'],
                'users' => $user->id,
            ]);
        });
    }
}
