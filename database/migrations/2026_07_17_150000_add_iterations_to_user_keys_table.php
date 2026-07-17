<?php

use App\Helpers\EncryptionHelper;
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
        Schema::table('user_keys', function (Blueprint $table) {
            $table->unsignedInteger('iterations')->default(EncryptionHelper::PBKDF2_ITERATIONS)->after('salt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_keys', function (Blueprint $table) {
            $table->dropColumn('iterations');
        });
    }
};
