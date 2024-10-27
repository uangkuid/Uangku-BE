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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallets');
            $table->uuid('access_id');
            $table->string("amount");
            $table->enum("type", ['Out', 'In']);
            $table->timestamps();

            $table->foreign('wallets')->references('id')->on('wallets');
            $table->foreign('access_id')->references('id')->on('wallet_accesses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
