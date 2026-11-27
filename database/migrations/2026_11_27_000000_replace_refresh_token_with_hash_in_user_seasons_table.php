<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces user_seasons.refresh_token with a fixed-width, uniquely indexed
 * SHA-256 hash.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_seasons', function (Blueprint $table) {
            $table->char('refresh_token_hash', 64)->nullable()->after('users');
        });

        $seen = [];

        DB::table('user_seasons')->select('id', 'refresh_token')->chunkById(500, function ($sessions) use (&$seen) {
            foreach ($sessions as $session) {
                // The repository already wrote a SHA-256 into refresh_token;
                // only older rows hold the JWT itself. Hashing a hash again
                // would silently destroy the session.
                $hash = preg_match('/^[a-f0-9]{64}$/', $session->refresh_token)
                    ? $session->refresh_token
                    : hash('sha256', $session->refresh_token);

                if (isset($seen[$hash])) {
                    DB::table('user_seasons')->where('id', $session->id)->delete();

                    continue;
                }

                $seen[$hash] = true;

                DB::table('user_seasons')->where('id', $session->id)->update(['refresh_token_hash' => $hash]);
            }
        });

        Schema::table('user_seasons', function (Blueprint $table) {
            $table->char('refresh_token_hash', 64)->nullable(false)->change();
            $table->unique('refresh_token_hash');
            $table->dropColumn('refresh_token');
        });
    }

    /**
     * Reverse the migrations. Plaintext tokens cannot (and need not) be
     * recovered: the old column gets the hash, which is what the previous
     * repository code stored there.
     */
    public function down(): void
    {
        Schema::table('user_seasons', function (Blueprint $table) {
            $table->text('refresh_token')->nullable()->after('users');
        });

        DB::table('user_seasons')->update(['refresh_token' => DB::raw('refresh_token_hash')]);

        Schema::table('user_seasons', function (Blueprint $table) {
            $table->text('refresh_token')->nullable(false)->change();
            $table->dropUnique(['refresh_token_hash']);
            $table->dropColumn('refresh_token_hash');
        });
    }
};
