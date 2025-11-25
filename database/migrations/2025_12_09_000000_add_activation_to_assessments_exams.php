<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assessment_exams')) {
            return;
        }

        Schema::table('assessment_exams', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_exams', 'activation_path')) {
                $table->string('activation_path', 191)->nullable()->after('version');
            }
            if (!Schema::hasColumn('assessment_exams', 'activation_token')) {
                $table->string('activation_token', 64)->nullable()->after('activation_path');
                $table->index('activation_token', 'assess_exam_activation_token_idx');
            }
            if (!Schema::hasColumn('assessment_exams', 'activation_expires_at')) {
                $table->dateTime('activation_expires_at')->nullable()->after('activation_token');
            }
            if (!Schema::hasColumn('assessment_exams', 'activation_used_at')) {
                $table->dateTime('activation_used_at')->nullable()->after('activation_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assessment_exams')) {
            return;
        }

        Schema::table('assessment_exams', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_exams', 'activation_used_at')) {
                $table->dropColumn('activation_used_at');
            }
            if (Schema::hasColumn('assessment_exams', 'activation_expires_at')) {
                $table->dropColumn('activation_expires_at');
            }
            if (Schema::hasColumn('assessment_exams', 'activation_token')) {
                $table->dropIndex('assess_exam_activation_token_idx');
                $table->dropColumn('activation_token');
            }
            if (Schema::hasColumn('assessment_exams', 'activation_path')) {
                $table->dropColumn('activation_path');
            }
        });
    }
};
