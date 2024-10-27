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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('users');
            $table->uuid('categories');
            $table->uuid('sub_categories')->nullable();
            $table->uuid('wallets');
            $table->uuid('transaction_type');
            $table->uuid('families')->nullable();
            $table->string('note');
            $table->double('amount');
            $table->timestamps();

            $table->foreign('users')->references('id')->on('users');
            $table->foreign('wallets')->references('id')->on('wallet_transactions');
            $table->foreign('categories')->references('id')->on('categories');
            $table->foreign('sub_categories')->references('id')->on('sub_categories');
            $table->foreign('transaction_type')->references('id')->on('transaction_types');
            $table->foreign('families')->references('id')->on('families');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
