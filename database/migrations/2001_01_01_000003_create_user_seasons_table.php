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
        Schema::create('user_seasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('users')->index();
            $table->text('refresh_token');
            $table->timestamps();

            $table->foreign('users')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_seasons');
    }
};
