<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet')->index();
            $table->uuid('wallet_transaction'); // referensi ke wallet_transactions terakhir
            $table->text('balance'); // RSA-encrypte
            $table->timestamps();

            $table->foreign('wallet')->references('id')->on('wallets');
            $table->foreign('wallet_transaction')->references('id')->on('wallet_transactions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_snapshots');
    }
};
