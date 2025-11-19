<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assessment_question_options')) {
            Schema::create('assessment_question_options', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->string('label');
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['question_id','position']);
            });
        }

        if (!Schema::hasTable('assessment_answer_keys')) {
            Schema::create('assessment_answer_keys', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedBigInteger('option_id')->index();
                $t->timestamps();
                $t->unique(['question_id','option_id']);
            });
        }

        if (!Schema::hasTable('assessment_exam_questions')) {
            Schema::create('assessment_exam_questions', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_id')->index();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedInteger('position')->default(0);
                $t->timestamps();
                $t->unique(['exam_id','question_id']);
                $t->index(['exam_id','position']);
            });
        }

        if (!Schema::hasTable('assessment_question_categories')) {
            Schema::create('assessment_question_categories', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedBigInteger('category_id')->index();
                $t->timestamps();
                $t->unique(['question_id','category_id']);
            });
        }

        if (!Schema::hasTable('assessment_attempts')) {
            Schema::create('assessment_attempts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_id')->index();
                $t->unsignedBigInteger('user_id')->index();
                $t->string('status')->default('draft'); // draft|started|submitted|expired
                $t->timestamp('started_at')->nullable();
                $t->json('frozen_question_ids')->nullable();
                $t->unsignedInteger('total_score')->nullable();
                $t->timestamps();
                $t->index(['exam_id','user_id']);
            });
        }

        // Ensure assessment_questions has Phase 1C fields where missing
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_questions','selection_mode')) {
                    $t->enum('selection_mode', ['single','multiple'])->default('single');
                }
                if (!Schema::hasColumn('assessment_questions','difficulty')) {
                    $t->enum('difficulty', ['easy','medium','hard','very_hard'])->default('easy');
                }
            });
        }

        // Ensure assessment_exams has fields for manual & config toggles
        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_exams','time_limit_seconds')) {
                    $t->unsignedInteger('time_limit_seconds')->nullable();
                }
                if (!Schema::hasColumn('assessment_exams','shuffle_questions')) {
                    $t->boolean('shuffle_questions')->default(false);
                }
                if (!Schema::hasColumn('assessment_exams','shuffle_options')) {
                    $t->boolean('shuffle_options')->default(false);
                }
                if (Schema::hasColumn('assessment_exams','assembly_mode')) {
                    // add 'manual' to enum if necessary (can't modify native enum easily across DBs, store as string)
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_question_categories');
        Schema::dropIfExists('assessment_exam_questions');
        Schema::dropIfExists('assessment_answer_keys');
        Schema::dropIfExists('assessment_question_options');
    }
};

