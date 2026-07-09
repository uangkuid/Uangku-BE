<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lifecycle user: status akun + soft delete untuk manajemen dari panel admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->index()->after('email_verified_at');
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->string('suspended_reason')->nullable()->after('suspended_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'suspended_at', 'suspended_reason', 'deleted_at']);
        });
    }
};
