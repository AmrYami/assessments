<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Answers Library
        if (!Schema::hasTable('assessment_answers')) {
            Schema::create('assessment_answers', function (Blueprint $t) {
                $t->id();
                $t->string('slug')->unique();
                $t->string('label');
                $t->enum('kind', ['option','input'])->default('option');
                $t->enum('input_type', ['text','textarea'])->nullable();
                $t->json('validation_json')->nullable();
                $t->boolean('is_active')->default(true);
                $t->softDeletes();
                $t->timestamps();
            });
        }

        // Question ↔ Answer (many-to-many with link params)
        if (!Schema::hasTable('assessment_question_answers')) {
            Schema::create('assessment_question_answers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedBigInteger('answer_id')->index();
                $t->unsignedInteger('position')->default(0);
                $t->boolean('is_active')->default(true);
                $t->enum('source_mode', ['linked','copied'])->default('linked');
                $t->json('params_json')->nullable();
                $t->softDeletes();
                $t->timestamps();
                $t->unique(['question_id','answer_id']);
                $t->index(['question_id','position']);
            });
        }

        // Scoped placements & versioning
        if (!Schema::hasTable('assessment_question_placements')) {
            Schema::create('assessment_question_placements', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedBigInteger('category_id')->nullable()->index();
                $t->unsignedBigInteger('topic_id')->nullable()->index();
                $t->unsignedInteger('placement_version')->default(1);
                $t->boolean('is_active')->default(true);
                $t->softDeletes();
                $t->timestamps();
                $t->index(['question_id','category_id','topic_id'], 'aqp_qct_idx');
            });
        }

        if (!Schema::hasTable('assessment_exam_placements')) {
            Schema::create('assessment_exam_placements', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_id')->index();
                $t->unsignedBigInteger('category_id')->nullable()->index();
                $t->unsignedBigInteger('topic_id')->nullable()->index();
                $t->unsignedInteger('placement_version')->default(1);
                $t->boolean('is_active')->default(true);
                $t->softDeletes();
                $t->timestamps();
                $t->index(['exam_id','category_id','topic_id'], 'aep_ect_idx');
            });
        }

        // Amend Questions: note flags
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_questions','note_enabled')) {
                    $t->boolean('note_enabled')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('assessment_questions','note_required')) {
                    $t->boolean('note_required')->default(false)->after('note_enabled');
                }
            });
        }

        // Attempt Answers: input values + note text
        if (Schema::hasTable('assessment_attempt_answers')) {
            Schema::table('assessment_attempt_answers', function (Blueprint $t) {
                if (!Schema::hasColumn('assessment_attempt_answers','input_values')) {
                    $t->json('input_values')->nullable()->after('option_ids');
                }
                if (!Schema::hasColumn('assessment_attempt_answers','note_text')) {
                    $t->longText('note_text')->nullable()->after('input_values');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_attempt_answers')) {
            Schema::table('assessment_attempt_answers', function (Blueprint $t) {
                if (Schema::hasColumn('assessment_attempt_answers','note_text')) $t->dropColumn('note_text');
                if (Schema::hasColumn('assessment_attempt_answers','input_values')) $t->dropColumn('input_values');
            });
        }
        Schema::dropIfExists('assessment_exam_placements');
        Schema::dropIfExists('assessment_question_placements');
        Schema::dropIfExists('assessment_question_answers');
        Schema::dropIfExists('assessment_answers');
    }
};
