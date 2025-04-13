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
        Schema::create('family_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('family')->index();
            $table->text('private_key');
            $table->text('public_key');
            $table->timestamps();

            $table->foreign('family')->references('id')->on('families');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_keys');
    }
};
