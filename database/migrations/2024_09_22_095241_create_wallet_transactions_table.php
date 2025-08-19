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
            $table->uuid('access');
            $table->uuid('transaction_type');
            $table->uuid('transaction_id');
            $table->text("amount");
            $table->uuid('updated_by');
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('wallets')->references('id')->on('wallets');
            $table->foreign('access')->references('id')->on('wallet_accesses');
            $table->foreign('transaction_type')->references('id')->on('transaction_types');
            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('updated_by')->references('id')->on('users');
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
