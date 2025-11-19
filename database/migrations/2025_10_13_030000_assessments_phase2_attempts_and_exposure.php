<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Attempt answers: stores user's selected option ids per question
        if (!Schema::hasTable('assessment_attempt_answers')) {
            Schema::create('assessment_attempt_answers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('attempt_id')->index();
                $t->unsignedBigInteger('question_id')->index();
                $t->json('option_ids'); // array of option ids
                $t->timestamps();
                $t->unique(['attempt_id','question_id']);
            });
        }

        // Exposure ledger (optional)
        if (!Schema::hasTable('assessment_question_exposures')) {
            Schema::create('assessment_question_exposures', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedInteger('seen_count')->default(0);
                $t->timestamp('last_seen_at')->nullable();
                $t->timestamps();
                $t->unique(['user_id','question_id']);
            });
        }

        // Extend attempts for Phase 2 lifecycle
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_attempts','expires_at')) {
                    $t->timestamp('expires_at')->nullable()->after('started_at');
                }
                if (!Schema::hasColumn('assessment_attempts','seed')) {
                    $t->unsignedBigInteger('seed')->nullable()->after('expires_at');
                }
                if (!Schema::hasColumn('assessment_attempts','percent')) {
                    $t->unsignedInteger('percent')->nullable()->after('total_score');
                }
                if (!Schema::hasColumn('assessment_attempts','passed')) {
                    $t->boolean('passed')->nullable()->after('percent');
                }
                if (!Schema::hasColumn('assessment_attempts','result_json')) {
                    $t->json('result_json')->nullable()->after('passed');
                }
            });
        }

        // Extend exams for pass rule and retake policy
        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_exams','pass_type')) {
                    $t->enum('pass_type', ['score','percent'])->default('percent');
                }
                if (!Schema::hasColumn('assessment_exams','pass_value')) {
                    $t->unsignedInteger('pass_value')->default(70); // default 70%
                }
                if (!Schema::hasColumn('assessment_exams','max_attempts')) {
                    $t->unsignedInteger('max_attempts')->default(1);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempt_answers');
        Schema::dropIfExists('assessment_question_exposures');
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_attempts','result_json')) $t->dropColumn('result_json');
                if (Schema::hasColumn('assessment_attempts','passed')) $t->dropColumn('passed');
                if (Schema::hasColumn('assessment_attempts','percent')) $t->dropColumn('percent');
                if (Schema::hasColumn('assessment_attempts','seed')) $t->dropColumn('seed');
                if (Schema::hasColumn('assessment_attempts','expires_at')) $t->dropColumn('expires_at');
            });
        }
        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_exams','pass_type')) $t->dropColumn('pass_type');
                if (Schema::hasColumn('assessment_exams','pass_value')) $t->dropColumn('pass_value');
                if (Schema::hasColumn('assessment_exams','max_attempts')) $t->dropColumn('max_attempts');
            });
        }
    }
};

