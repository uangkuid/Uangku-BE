<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-recipient wrapping of the family private key: each member gets their
     * own copy, wrapped (client-side) to their own public key. Replaces the
     * old single shared "family secret key" model so revoking a member can
     * remove just their row instead of invalidating a shared secret.
     */
    public function up(): void
    {
        Schema::create('family_member_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('family')->index();
            $table->uuid('users')->index();
            $table->text('wrapped_private_key');
            $table->timestamps();

            $table->foreign('family')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('users')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['family', 'users']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_member_keys');
    }
};
