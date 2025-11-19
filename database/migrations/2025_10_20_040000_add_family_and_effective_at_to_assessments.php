<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_questions','parent_id')) {
                    $t->unsignedBigInteger('parent_id')->nullable()->after('id');
                    $t->index('parent_id');
                }
                if (!Schema::hasColumn('assessment_questions','version_int')) {
                    $t->unsignedInteger('version_int')->default(1)->after('slug');
                }
            });
            DB::table('assessment_questions')->whereNull('version_int')->update(['version_int'=>1]);
        }
        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_exams','parent_id')) {
                    $t->unsignedBigInteger('parent_id')->nullable()->after('id');
                    $t->index('parent_id');
                }
                if (!Schema::hasColumn('assessment_exams','version_int')) {
                    $t->unsignedInteger('version_int')->default(1)->after('slug');
                }
            });
            DB::table('assessment_exams')->whereNull('version_int')->update(['version_int'=>1]);
        }
        if (Schema::hasTable('assessment_question_placements')) {
            Schema::table('assessment_question_placements', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_question_placements','effective_at')) {
                    $t->timestamp('effective_at')->nullable()->after('placement_version');
                }
            });
        }
        if (Schema::hasTable('assessment_exam_placements')) {
            Schema::table('assessment_exam_placements', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_exam_placements','effective_at')) {
                    $t->timestamp('effective_at')->nullable()->after('placement_version');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_question_placements')) {
            Schema::table('assessment_question_placements', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_question_placements','effective_at')) $t->dropColumn('effective_at');
            });
        }
        if (Schema::hasTable('assessment_exam_placements')) {
            Schema::table('assessment_exam_placements', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_exam_placements','effective_at')) $t->dropColumn('effective_at');
            });
        }
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_questions','version_int')) $t->dropColumn('version_int');
                if (Schema::hasColumn('assessment_questions','parent_id')) { $t->dropIndex(['parent_id']); $t->dropColumn('parent_id'); }
            });
        }
        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_exams','version_int')) $t->dropColumn('version_int');
                if (Schema::hasColumn('assessment_exams','parent_id')) { $t->dropIndex(['parent_id']); $t->dropColumn('parent_id'); }
            });
        }
    }
};

