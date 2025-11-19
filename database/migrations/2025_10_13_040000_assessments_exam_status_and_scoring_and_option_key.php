<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Exams status
        if (Schema::hasTable('assessment_exams') && !Schema::hasColumn('assessment_exams','status')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                $t->enum('status', ['draft','published','archived'])->default('draft')->after('is_published');
            });
            // Try to migrate existing is_published values to status
            \DB::table('assessment_exams')->where('is_published', 1)->update(['status' => 'published']);
        }

        // Attempts scoring clarity
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_attempts','score_obtained')) {
                    $t->unsignedInteger('score_obtained')->nullable()->after('total_score');
                }
                if (!Schema::hasColumn('assessment_attempts','score_max')) {
                    $t->unsignedInteger('score_max')->nullable()->after('score_obtained');
                }
            });
        }

        // Option key for export/i18n
        if (Schema::hasTable('assessment_question_options') && !Schema::hasColumn('assessment_question_options','key')) {
            Schema::table('assessment_question_options', function (Blueprint $t) {
                $t->string('key')->nullable()->after('label');
                $t->unique(['question_id','key']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_exams') && Schema::hasColumn('assessment_exams','status')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                $t->dropColumn('status');
            });
        }
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_attempts','score_max')) $t->dropColumn('score_max');
                if (Schema::hasColumn('assessment_attempts','score_obtained')) $t->dropColumn('score_obtained');
            });
        }
        if (Schema::hasTable('assessment_question_options') && Schema::hasColumn('assessment_question_options','key')) {
            Schema::table('assessment_question_options', function (Blueprint $t) {
                $t->dropUnique(['question_id','key']);
                $t->dropColumn('key');
            });
        }
    }
};

