<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Extend enum values for categories.on_fail_action
        try {
            DB::statement("ALTER TABLE categories MODIFY on_fail_action ENUM('reject','block_profile','allow_profile','block_profile_reject','allow_profile_reject') DEFAULT 'block_profile'");
        } catch (\Throwable $e) {
            // Some drivers don't support enum alteration; ignore silently
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE categories MODIFY on_fail_action ENUM('reject','block_profile','allow_profile') DEFAULT 'block_profile'");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};

