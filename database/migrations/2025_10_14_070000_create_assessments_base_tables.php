<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assessment_topics')) {
            Schema::create('assessment_topics', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('position')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('assessment_questions')) {
            Schema::create('assessment_questions', function (Blueprint $t) {
                $t->id();
                $t->string('slug')->unique();
                $t->text('text');
                $t->enum('selection_mode', ['single','multiple'])->default('single');
                $t->unsignedInteger('weight')->default(1);
                $t->enum('difficulty', ['easy','medium','hard','very_hard'])->default('easy');
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('assessment_question_topics')) {
            Schema::create('assessment_question_topics', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('question_id')->index();
                $t->unsignedBigInteger('topic_id')->index();
                $t->timestamps();
                $t->unique(['question_id','topic_id']);
            });
        }

        if (!Schema::hasTable('assessment_exams')) {
            Schema::create('assessment_exams', function (Blueprint $t) {
                $t->id();
                $t->string('title');
                $t->string('slug')->unique();
                $t->unsignedBigInteger('category_id')->nullable();
                $t->string('assembly_mode')->default('manual'); // manual|by_count|by_score
                $t->unsignedInteger('question_count')->nullable();
                $t->unsignedInteger('target_total_score')->nullable();
                $t->boolean('is_published')->default(false);
                $t->string('status')->default('draft'); // draft|published|archived
                $t->unsignedInteger('time_limit_seconds')->nullable();
                $t->boolean('shuffle_questions')->default(false);
                $t->boolean('shuffle_options')->default(false);
                $t->enum('pass_type', ['score','percent'])->default('percent');
                $t->unsignedInteger('pass_value')->default(70);
                $t->unsignedInteger('max_attempts')->default(1);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('assessment_exam_topics')) {
            Schema::create('assessment_exam_topics', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('exam_id')->index();
                $t->unsignedBigInteger('topic_id')->index();
                $t->timestamps();
                $t->unique(['exam_id','topic_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_exam_topics');
        Schema::dropIfExists('assessment_exams');
        Schema::dropIfExists('assessment_question_topics');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessment_topics');
    }
};

