<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_questions', 'max_choices')) {
                    $table->unsignedInteger('max_choices')->nullable()->after('response_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_questions') && Schema::hasColumn('assessment_questions', 'max_choices')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                $table->dropColumn('max_choices');
            });
        }
    }
};
