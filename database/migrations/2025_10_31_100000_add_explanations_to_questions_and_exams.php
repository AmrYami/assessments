<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('assessment_questions') && !Schema::hasColumn('assessment_questions', 'explanation')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                $table->text('explanation')->nullable()->after('note_hint');
            });
        }

        if (Schema::hasTable('assessment_exams') && !Schema::hasColumn('assessment_exams', 'show_explanations')) {
            Schema::table('assessment_exams', function (Blueprint $table) {
                $table->boolean('show_explanations')->default(false)->after('shuffle_options');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_questions') && Schema::hasColumn('assessment_questions', 'explanation')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                $table->dropColumn('explanation');
            });
        }

        if (Schema::hasTable('assessment_exams') && Schema::hasColumn('assessment_exams', 'show_explanations')) {
            Schema::table('assessment_exams', function (Blueprint $table) {
                $table->dropColumn('show_explanations');
            });
        }
    }
};
