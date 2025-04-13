<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateRSAKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uangku:generate-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate RSA public and private keys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Konfigurasi untuk pembuatan kunci
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Membuat pasangan kunci
        $res = openssl_pkey_new($config);

        // Ekstrak kunci privat
        openssl_pkey_export($res, $privateKey);

        // Ekstrak kunci publik
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails["key"];

        $this->info("===== PRIVATE KEY =====");
        $this->line(base64_encode($privateKey)); // pakai line untuk plain text
        $this->info("===== PUBLIC KEY =====");
        $this->line(base64_encode($publicKey));

    }
}
