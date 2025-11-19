<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_questions','schema_hash')) {
                    $t->char('schema_hash', 64)->nullable()->after('version_int');
                }
            });
        }
        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_exams','schema_hash')) {
                    $t->char('schema_hash', 64)->nullable()->after('version_int');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_questions') && Schema::hasColumn('assessment_questions','schema_hash')) {
            Schema::table('assessment_questions', function (Blueprint $t) { $t->dropColumn('schema_hash'); });
        }
        if (Schema::hasTable('assessment_exams') && Schema::hasColumn('assessment_exams','schema_hash')) {
            Schema::table('assessment_exams', function (Blueprint $t) { $t->dropColumn('schema_hash'); });
        }
    }
};

