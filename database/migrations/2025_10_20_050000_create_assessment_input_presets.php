<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assessment_input_presets')) {
            Schema::create('assessment_input_presets', function (Blueprint $t) {
                $t->id();
                $t->string('slug')->unique();
                $t->string('label');
                $t->enum('input_type', ['text','textarea'])->default('text');
                $t->json('spec_json')->nullable();
                $t->boolean('is_active')->default(true);
                $t->softDeletes();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_input_presets');
    }
};

