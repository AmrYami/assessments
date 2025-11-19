<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('assessment_exams') && !Schema::hasColumn('assessment_exams','difficulty_split_json')) {
            Schema::table('assessment_exams', function (Blueprint $t) {
                $t->json('difficulty_split_json')->nullable()->after('question_count');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_exams') && Schema::hasColumn('assessment_exams','difficulty_split_json')) {
            Schema::table('assessment_exams', function (Blueprint $t) { $t->dropColumn('difficulty_split_json'); });
        }
    }
};

