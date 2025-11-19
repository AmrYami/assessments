<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assessment_question_widgets')) {
            Schema::create('assessment_question_widgets', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->string('key');
                $t->enum('widget_type', ['text','textarea']);
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->boolean('required')->default(false);
                $t->string('placeholder')->nullable();
                $t->integer('min')->nullable();
                $t->integer('max')->nullable();
                $t->string('regex')->nullable();
                $t->enum('eval_mode', ['manual'])->default('manual');
                $t->softDeletes();
                $t->timestamps();
                $t->unique(['question_id','key']);
            });
        }

        if (Schema::hasTable('assessment_attempt_answers')) {
            Schema::table('assessment_attempt_answers', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_attempt_answers','awarded_score')) $t->integer('awarded_score')->default(0)->after('input_values');
                if (!Schema::hasColumn('assessment_attempt_answers','is_correct')) $t->boolean('is_correct')->nullable()->after('awarded_score');
                if (!Schema::hasColumn('assessment_attempt_answers','needs_review')) $t->boolean('needs_review')->default(0)->after('is_correct');
            });
        }

        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_attempts','review_status')) $t->string('review_status')->default('not_needed')->after('passed');
                if (!Schema::hasColumn('assessment_attempts','score_auto')) $t->unsignedInteger('score_auto')->default(0)->after('review_status');
                if (!Schema::hasColumn('assessment_attempts','score_manual')) $t->unsignedInteger('score_manual')->default(0)->after('score_auto');
                if (!Schema::hasColumn('assessment_attempts','reviewed_at')) $t->timestamp('reviewed_at')->nullable()->after('score_manual');
                if (!Schema::hasColumn('assessment_attempts','reviewer_id')) $t->unsignedBigInteger('reviewer_id')->nullable()->after('reviewed_at');
                if (!Schema::hasColumn('assessment_attempts','review_notes')) $t->longText('review_notes')->nullable()->after('reviewer_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $t) {
                foreach (['review_notes','reviewer_id','reviewed_at','score_manual','score_auto','review_status'] as $c) {
                    if (Schema::hasColumn('assessment_attempts',$c)) $t->dropColumn($c);
                }
            });
        }
        if (Schema::hasTable('assessment_attempt_answers')) {
            Schema::table('assessment_attempt_answers', function (Blueprint $t) {
                foreach (['needs_review','is_correct','awarded_score'] as $c) {
                    if (Schema::hasColumn('assessment_attempt_answers',$c)) $t->dropColumn($c);
                }
            });
        }
        Schema::dropIfExists('assessment_question_widgets');
    }
};

