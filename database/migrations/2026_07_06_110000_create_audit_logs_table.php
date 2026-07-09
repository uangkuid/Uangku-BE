<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak audit aksi staff di panel admin. Immutable (hanya created_at).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Aktor (staff). Nullable agar log tetap ada meski staff dihapus.
            $table->uuid('staff_id')->nullable();

            // Aksi mesin, mis. "user.suspend", "user.reset_pin".
            $table->string('action')->index();

            // Target polimorfik (opsional): mis. App\Models\User + id user.
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();

            // Ringkasan human-readable + konteks tambahan (NON-sensitif, zero-knowledge).
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['target_type', 'target_id']);
            $table->foreign('staff_id')->references('id')->on('staff_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
