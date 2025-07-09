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
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('categories');
            $table->uuid('users')->nullable()->default(null);
            $table->uuid('families')->nullable()->default(null);
            $table->string('name');
            $table->timestamps();

            $table->foreign('categories')->references('id')->on('categories');
            $table->foreign('users')->references('id')->on('users');
            $table->foreign('families')->references('id')->on('families');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};
