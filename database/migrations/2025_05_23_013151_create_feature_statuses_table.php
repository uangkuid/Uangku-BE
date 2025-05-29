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
        Schema::create('feature_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('feature_name')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('updated_by')->references('id')->on('staff_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_statuses');
    }
};
