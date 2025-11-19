<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('answer_usage_aggregates')) {
            Schema::create('answer_usage_aggregates', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('answer_id')->unique();
                $t->unsignedInteger('used_by_questions_count')->default(0);
                $t->unsignedInteger('used_by_placements_count')->default(0);
                $t->unsignedBigInteger('used_by_attempts_count')->default(0);
                $t->unsignedInteger('used_by_exam_placements_definite')->default(0);
                $t->unsignedInteger('used_by_exam_placements_potential')->default(0);
                $t->timestamp('last_used_at')->nullable();
                $t->timestamp('last_recomputed_at')->nullable();
                $t->timestamps();
                $t->index('answer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_usage_aggregates');
    }
};
