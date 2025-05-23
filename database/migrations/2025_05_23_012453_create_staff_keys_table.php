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
        Schema::create('staff_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staffs');
            $table->string('public_key');
            $table->string('private_key');
            $table->string('hashed_key');
            $table->string('hashed_pin');
            $table->foreign('staffs')->references('id')->on('staff_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_keys');
    }
};
