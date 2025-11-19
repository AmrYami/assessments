<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $t) {
                if (!Schema::hasColumn('categories','entrance_exam_id')) {
                    $t->unsignedBigInteger('entrance_exam_id')->nullable()->index()->after('description');
                }
                if (!Schema::hasColumn('categories','exam_trigger')) {
                    $t->enum('exam_trigger', ['none','on_register','after_approval'])->default('none')->after('entrance_exam_id');
                }
                if (!Schema::hasColumn('categories','on_fail_action')) {
                    $t->enum('on_fail_action', ['reject','block_profile','allow_profile'])->default('block_profile')->after('exam_trigger');
                }
                if (!Schema::hasColumn('categories','fail_message')) {
                    $t->longText('fail_message')->nullable()->after('on_fail_action');
                }
            });
        }

        if (!Schema::hasTable('assessment_exam_requirements')) {
            Schema::create('assessment_exam_requirements', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->unsignedBigInteger('exam_id')->index();
                $t->string('status')->default('required'); // required|in_progress|passed|failed
                $t->unsignedInteger('attempts_used')->default(0);
                $t->unsignedInteger('max_attempts')->nullable();
                $t->unsignedBigInteger('last_attempt_id')->nullable();
                $t->string('fail_action')->nullable(); // reject|block_profile|allow_profile (snapshotted from category)
                $t->timestamp('assigned_at')->nullable();
                $t->timestamps();
                $t->unique(['user_id','exam_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_exam_requirements');
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $t) {
                if (Schema::hasColumn('categories','fail_message')) $t->dropColumn('fail_message');
                if (Schema::hasColumn('categories','on_fail_action')) $t->dropColumn('on_fail_action');
                if (Schema::hasColumn('categories','exam_trigger')) $t->dropColumn('exam_trigger');
                if (Schema::hasColumn('categories','entrance_exam_id')) $t->dropColumn('entrance_exam_id');
            });
        }
    }
};

