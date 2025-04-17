<?php

namespace App\Console\Commands;

use App\Helpers\EncryptionHelper;
use Illuminate\Console\Command;

class GenerateSecretKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uangku:generate-secret-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Secret Key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Secret Key:");
        $this->line(EncryptionHelper::generateUsersSecretKey());
    }
}
