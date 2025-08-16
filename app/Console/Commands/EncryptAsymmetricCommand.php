<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Helpers\EncryptionHelper;

class EncryptAsymmetricCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encrypt:asymmetric {text} {publicKeyBase64}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt an amount using base64-encoded public key and asymmetric encryption';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amount = $this->argument('text');
        $publicKeyBase64 = $this->argument('publicKeyBase64');

        try {
            // Decode base64 public key
            $publicKey = base64_decode($publicKeyBase64);

            if (!$publicKey) {
                $this->error("Invalid base64 public key.");
                return Command::FAILURE;
            }

            // Encrypt
            $encrypted = EncryptionHelper::encryptAsymmetric($amount, $publicKey);

            $this->info("Encrypted amount:");
            $this->line($encrypted);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("Encryption failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
