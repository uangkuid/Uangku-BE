<?php

namespace App\Console\Commands;

use App\Helpers\EncryptionHelper;
use App\Models\UserKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Dumps deterministic, byte-exact test vectors so client implementations
 * (KMP, Vue) can verify their 2SKD/AES-GCM/RSA derivation is identical to
 * the server's reference implementation — see docs/encryption.md §4.2.
 *
 * Every case exposes intermediate values (kdfPass, kdfSecret, unlockKey),
 * not just the final authKey: if a client's authKey doesn't match, the
 * intermediate values pinpoint which step diverged instead of leaving the
 * client to debug blind (see faq-backend.md "Kenapa harus termasuk nilai
 * antara").
 *
 * WAJIB: this command only calls EncryptionHelper::deriveUnlockKey()/
 * deriveAuthKey() (or the primitives they're built from) — never
 * reimplements the derivation steps. That duplication is exactly what
 * caused the seeder to silently diverge from the documented contract
 * (faq-backend.md Blocker #1).
 */
class GenerateKdfVectors extends Command
{
    protected $signature = 'uangku:kdf-vectors
        {--output= : File path to write JSON to (default: docs/test-vectors/kdf-vectors.json)}
        {--iterations= : PBKDF2 iterations for Set A (default: the real contract value, EncryptionHelper::PBKDF2_ITERATIONS)}';

    protected $description = 'Generate 2SKD/AES-GCM/RSA test vectors clients must reproduce byte-for-byte (see docs/encryption.md §4.2)';

    public function handle(): int
    {
        $iterations = (int) ($this->option('iterations') ?: EncryptionHelper::PBKDF2_ITERATIONS);

        $vectors = [
            'generated_at' => now()->toIso8601String(),
            'contract' => 'docs/encryption.md §4.2 — per-user 16-byte RAW salt used for BOTH PBKDF2 and HKDF(kdfSecret), no literal "user-salt"',
            'setA_2skd' => $this->buildSetA($iterations),
            'setB_hkdf_salt_isolation' => $this->buildSetB(),
            'setC_aes_gcm_fixed_iv' => $this->buildSetC(),
            'setD_rsa' => $this->buildSetD(),
            'setE_hybrid_envelope' => $this->buildSetE(),
        ];

        $json = json_encode($vectors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $outputPath = $this->option('output') ?: base_path('docs/test-vectors/kdf-vectors.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $json."\n");

        $this->info("Wrote test vectors to {$outputPath}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSetA(int $iterations): array
    {
        $secretKey = 'UANGKU-ABC123-DEF456-GHI78-JKL90-MNO12';
        $salt = random_bytes(16);

        $cases = [
            ['label' => 'ascii_baseline', 'password' => 'CorrectHorse123!', 'proves' => 'Baseline — no normalization/encoding edge case involved.'],
            ['label' => 'nfc_cafe', 'password' => "caf\u{00E9}", 'proves' => 'café, precomposed NFC form (single U+00E9) — how Android/most keyboards encode this password.'],
            ['label' => 'nfd_cafe', 'password' => "cafe\u{0301}", 'proves' => 'café, decomposed NFD form (e + combining acute U+0301) — how macOS often encodes this password. Must derive the SAME auth_key as nfc_cafe if the client normalizes to NFC per §13.3; if PHP here does NOT normalize, this row will legitimately differ — that IS the point of shipping both.'],
            ['label' => 'emoji_padded', 'password' => " \u{1F510}pass ", 'proves' => 'Leading/trailing whitespace and a non-BMP emoji are NOT trimmed or stripped anywhere in the pipeline.'],
            ['label' => 'long_200_chars', 'password' => str_repeat('a', 200), 'proves' => 'No truncation at any step for long passwords.'],
        ];

        return array_map(function (array $case) use ($secretKey, $salt, $iterations) {
            $password = $case['password'];
            $kdfPass = EncryptionHelper::pbkdf2($password, $salt, $iterations, 32);
            $kdfSecret = EncryptionHelper::hkdf($secretKey, EncryptionHelper::INFO_SECRET_KEY, 32, $salt);
            $unlockKey = $kdfPass ^ $kdfSecret;
            $authKey = base64_encode(EncryptionHelper::hkdf($unlockKey, EncryptionHelper::INFO_AUTH, 32));

            return [
                'label' => $case['label'],
                'proves' => $case['proves'],
                'password' => $password,
                'password_hex' => bin2hex($password),
                'secret_key' => $secretKey,
                'hkdf_ikm_hex' => bin2hex($secretKey),
                'hkdf_salt_hex' => bin2hex($salt),
                'salt_b64' => base64_encode($salt),
                'salt_hex' => bin2hex($salt),
                'iterations' => $iterations,
                'kdf_pass_hex' => bin2hex($kdfPass),
                'kdf_secret_hex' => bin2hex($kdfSecret),
                'unlock_key_hex' => bin2hex($unlockKey),
                'auth_key' => $authKey,
            ];
        }, $cases);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSetB(): array
    {
        $ikm = random_bytes(32);
        $email = 'vector-email@example.com';

        return [
            'ikm_hex' => bin2hex($ikm),
            'info' => EncryptionHelper::INFO_SECRET_KEY,
            'salt_omitted_okm_hex' => bin2hex(EncryptionHelper::hkdf($ikm, EncryptionHelper::INFO_SECRET_KEY)),
            'salt_empty_string_okm_hex' => bin2hex(EncryptionHelper::hkdf($ikm, EncryptionHelper::INFO_SECRET_KEY, 32, '')),
            'salt_literal_user_salt_okm_hex' => bin2hex(EncryptionHelper::hkdf($ikm, EncryptionHelper::INFO_SECRET_KEY, 32, 'user-salt')),
            'salt_email_okm_hex' => bin2hex(EncryptionHelper::hkdf($ikm, EncryptionHelper::INFO_SECRET_KEY, 32, $email)),
            'note' => 'salt_omitted and salt_empty_string_okm_hex MUST be identical — confirms hash_hkdf(salt="") behaves as RFC 5869 zeros(HashLen), which authKey\'s derivation (hkdf() called with no $salt arg) relies on. The literal/email rows are historical comparison values only — NOT part of the current contract, which always uses the per-user 16-byte RAW salt for kdfSecret too (see Set A).',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSetC(): array
    {
        $key = random_bytes(32);
        $iv = random_bytes(12);
        $plaintext = 'uangku fixed-iv test vector plaintext';

        // EncryptionHelper::aesGcmEncrypt() always uses a random IV (correct
        // for production, useless for a byte-exact vector) — reimplemented
        // inline here ONLY to pin the IV, using the exact same container
        // format (ver‖iv‖ct‖tag) and primitives.
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        $container = base64_encode(chr(EncryptionHelper::CIPHER_VERSION_GCM).$iv.$ciphertext.$tag);

        return [
            'key_hex' => bin2hex($key),
            'iv_hex' => bin2hex($iv),
            'plaintext' => $plaintext,
            'plaintext_hex' => bin2hex($plaintext),
            'container_b64' => $container,
            'note' => 'Confirm both directions: your AES-GCM implementation must decrypt container_b64 with key_hex back to plaintext, AND EncryptionHelper::aesGcmDecrypt() must be able to open a container YOUR implementation produces with the same key/iv/plaintext.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSetD(): array
    {
        try {
            $userKey = UserKey::query()->whereNotNull('salt')->first();
        } catch (\Throwable) {
            // No DB configured/reachable — fall through to the synthetic vector below.
            $userKey = null;
        }

        if ($userKey !== null) {
            return [
                'source' => 'dev_db (user_keys row)',
                'public_key_b64' => $userKey->public_key,
                'wrapped_private_key' => $userKey->private_key,
                'note' => 'unlock_key_hex is not derivable from stored data alone — pair this with the password/secret key used to create this account (e.g. the seeder credentials, see database/seeders/UserSeeder.php) and derive it via EncryptionHelper::deriveUnlockKey(), then use it to open wrapped_private_key with aesGcmDecrypt().',
            ];
        }

        // No seeded account available in this environment — generate a
        // synthetic keypair so the command still produces something usable.
        // Run `php artisan db:seed` first for a real dev-DB-sourced vector.
        $keyPair = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($keyPair, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($keyPair)['key'];
        $unlockKey = random_bytes(32);
        $wrapped = EncryptionHelper::aesGcmEncrypt($privateKeyPem, $unlockKey);

        return [
            'source' => 'synthetic — no seeded user_keys row found. Run `php artisan db:seed` then re-run this command for a real dev-DB-sourced vector.',
            'public_key_b64' => base64_encode($publicKeyPem),
            'wrapped_private_key' => $wrapped,
            'unlock_key_hex' => bin2hex($unlockKey),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSetE(): array
    {
        $keyPair = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($keyPair, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($keyPair)['key'];

        $dataKey = random_bytes(32);
        // Canonical numeric form, docs/encryption.md §8: Rp150.000,00 as a
        // minor-unit integer string.
        $plaintext = '15000000';
        $ct = EncryptionHelper::aesGcmEncrypt($plaintext, $dataKey);

        return [
            'plaintext' => $plaintext,
            'data_key_hex' => bin2hex($dataKey),
            'envelope' => [
                'v' => 2,
                'ek' => null,
                'ct' => $ct,
            ],
            'public_key_b64' => base64_encode($publicKeyPem),
            'private_key_pem_b64' => base64_encode($privateKeyPem),
            'note' => 'envelope.ek is intentionally omitted: the server never performs RSA-OAEP-SHA256 itself (docs/encryption.md §4 — "Klien saja"), and PHP\'s built-in openssl_public_encrypt() OAEP padding is fixed to SHA-1, not SHA-256, so a PHP-produced ek would be actively misleading rather than authoritative. To validate: RSA-OAEP-SHA256-encrypt data_key_hex with public_key_b64 using YOUR implementation, confirm RSA-OAEP-SHA256-decrypt with private_key_pem_b64 recovers the same data_key_hex, and confirm aesGcmDecrypt(ct, data_key_hex) recovers plaintext.',
        ];
    }
}
