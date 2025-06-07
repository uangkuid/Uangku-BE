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
        Schema::create('wallet_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('users');
            $table->uuid('wallets');
            $table->boolean("is_active");
            $table->enum("role", ['Admin', 'Member'])->default('Member');
            $table->timestamps();

            $table->foreign('users')->references('id')->on('users');
            $table->foreign('wallets')->references('id')->on('wallets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_accesses');
    }
};
