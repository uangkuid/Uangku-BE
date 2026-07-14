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
        Schema::create('user_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('users')->index();
            $table->text('private_key');
            $table->text('public_key');
            $table->string('salt', 255);
            $table->string('hashed_pin', 255)->nullable();
            $table->timestamps();

            $table->foreign('users')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_keys');
    }
};
