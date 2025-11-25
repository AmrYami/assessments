<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('assessment_answer_sets')) {
            Schema::create('assessment_answer_sets', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('assessment_answer_set_items')) {
            Schema::create('assessment_answer_set_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('answer_set_id')->index();
                $table->string('label');
                $table->string('value')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
                $table->index(['answer_set_id', 'position']);
            });
        }

        if (Schema::hasTable('assessment_question_answers')) {
            Schema::dropIfExists('assessment_question_answers');
        }

        if (!Schema::hasTable('assessment_question_answer_links')) {
            Schema::create('assessment_question_answer_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('question_id')->index();
                $table->unsignedBigInteger('answer_set_item_id')->index();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_correct')->default(false);
                $table->string('label_override')->nullable();
                $table->string('value_override')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->unique(['question_id', 'answer_set_item_id'], 'aqal_question_item_unique');
            });
        }

        if (!Schema::hasTable('assessment_question_response_parts')) {
            Schema::create('assessment_question_response_parts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('question_id')->index();
                $table->string('key');
                $table->string('label');
                $table->enum('input_type', ['text', 'textarea'])->default('text');
                $table->boolean('required')->default(false);
                $table->enum('validation_mode', ['none', 'exact', 'regex'])->default('none');
                $table->string('validation_value')->nullable();
                $table->unsignedInteger('weight_share')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->softDeletes();
                $table->timestamps();
                // Short index names to stay under MySQL identifier limits with prefixes
                $table->unique(['question_id', 'key'], 'aqrp_qid_key_unique');
                $table->index(['question_id', 'position'], 'aqrp_qid_pos_idx');
            });
        }

        if (!Schema::hasTable('assessment_attempt_text_answers')) {
            Schema::create('assessment_attempt_text_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attempt_id')->index();
                $table->unsignedBigInteger('question_id')->index();
                $table->string('part_key')->nullable()->index();
                $table->longText('text_value')->nullable();
                $table->boolean('is_valid')->nullable();
                $table->integer('score_awarded')->nullable();
                $table->softDeletes();
                $table->timestamps();
                $table->unique(['attempt_id', 'question_id', 'part_key'], 'aat_answers_unique');
            });
        }

        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_questions', 'response_type')) {
                    $table->string('response_type', 32)->default('single_choice')->after('slug');
                }
                if (!Schema::hasColumn('assessment_questions', 'note_hint')) {
                    $table->string('note_hint')->nullable()->after('note_required');
                }
                if (!Schema::hasColumn('assessment_questions', 'origin_id')) {
                    $table->unsignedBigInteger('origin_id')->nullable()->after('note_hint');
                    $table->index('origin_id');
                }
                if (!Schema::hasColumn('assessment_questions', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('origin_id');
                }
            });
        }

        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_exams', 'origin_id')) {
                    $table->unsignedBigInteger('origin_id')->nullable()->after('difficulty_split_json');
                    $table->index('origin_id');
                }
                if (!Schema::hasColumn('assessment_exams', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('origin_id');
                }
            });
        }

        if (Schema::hasTable('assessment_answer_keys')) {
            Schema::table('assessment_answer_keys', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_answer_keys', 'answer_set_item_id')) {
                    $table->unsignedBigInteger('answer_set_item_id')->nullable()->after('option_id');
                    $table->index('answer_set_item_id');
                }
            });
        }

        if (Schema::hasTable('assessment_question_options')) {
            Schema::table('assessment_question_options', function (Blueprint $table) {
                if (!Schema::hasColumn('assessment_question_options', 'answer_set_item_id')) {
                    $table->unsignedBigInteger('answer_set_item_id')->nullable()->after('question_id');
                    $table->index('answer_set_item_id', 'aqo_answer_set_item_idx');
                }
            });
        }

        // Backfill response type and versioning information
        if (Schema::hasColumn('assessment_questions', 'selection_mode')) {
            DB::table('assessment_questions')
                ->whereNull('response_type')
                ->update(['response_type' => 'single_choice']);

            $singleIds = DB::table('assessment_questions')->where('selection_mode', 'single')->pluck('id');
            if ($singleIds->isNotEmpty()) {
                DB::table('assessment_questions')->whereIn('id', $singleIds)->update(['response_type' => 'single_choice']);
            }
            $multiIds = DB::table('assessment_questions')->where('selection_mode', 'multiple')->pluck('id');
            if ($multiIds->isNotEmpty()) {
                DB::table('assessment_questions')->whereIn('id', $multiIds)->update(['response_type' => 'multiple_choice']);
            }
        }

        // Drop legacy columns that have been migrated to origin/version/response_type model.
        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                if (Schema::hasColumn('assessment_questions', 'selection_mode')) {
                    $table->dropColumn('selection_mode');
                }
                if (Schema::hasColumn('assessment_questions', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('assessment_questions', 'version_int')) {
                    $table->dropColumn('version_int');
                }
            });
        }

        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $table) {
                if (Schema::hasColumn('assessment_exams', 'parent_id')) {
                    $table->dropColumn('parent_id');
                }
                if (Schema::hasColumn('assessment_exams', 'version_int')) {
                    $table->dropColumn('version_int');
                }
            });
        }

        if (Schema::hasTable('assessment_questions')) {
            if (Schema::hasColumn('assessment_questions', 'parent_id')) {
                $questions = DB::table('assessment_questions')->select('id', 'parent_id', 'version_int')->get();
                foreach ($questions as $question) {
                    $originId = $question->parent_id ?: $question->id;
                    DB::table('assessment_questions')->where('id', $question->id)->update([
                        'origin_id' => $originId,
                        'version' => max(1, (int) ($question->version_int ?? 1)),
                    ]);
                }
            } else {
                DB::table('assessment_questions')->whereNull('origin_id')->update(['origin_id' => DB::raw('id')]);
            }
        }

        if (Schema::hasTable('assessment_exams')) {
            if (Schema::hasColumn('assessment_exams', 'parent_id')) {
                $exams = DB::table('assessment_exams')->select('id', 'parent_id', 'version_int')->get();
                foreach ($exams as $exam) {
                    $originId = $exam->parent_id ?: $exam->id;
                    DB::table('assessment_exams')->where('id', $exam->id)->update([
                        'origin_id' => $originId,
                        'version' => max(1, (int) ($exam->version_int ?? 1)),
                    ]);
                }
            } else {
                DB::table('assessment_exams')->whereNull('origin_id')->update(['origin_id' => DB::raw('id')]);
            }
        }

        // Backfill Answer Sets for existing per-question options
        if (Schema::hasTable('assessment_questions') && Schema::hasTable('assessment_question_options')) {
            $now = now();
            $questions = DB::table('assessment_questions')->select('id', 'slug', 'response_type')->get();
            foreach ($questions as $question) {
                $options = DB::table('assessment_question_options')
                    ->where('question_id', $question->id)
                    ->orderBy('position')
                    ->get();
                if ($options->isEmpty()) {
                    continue;
                }
                $slug = 'q-'.$question->id;
                $set = DB::table('assessment_answer_sets')->where('slug', $slug)->first();
                if (!$set) {
                    $setId = DB::table('assessment_answer_sets')->insertGetId([
                        'name' => 'Question '.$question->id.' Set',
                        'slug' => $slug,
                        'description' => 'Auto-generated from existing question options.',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $setId = $set->id;
                }

                $optionMap = [];
                foreach ($options as $opt) {
                    $item = DB::table('assessment_answer_set_items')->where('answer_set_id', $setId)->where('label', $opt->label)->first();
                    if (!$item) {
                        $itemId = DB::table('assessment_answer_set_items')->insertGetId([
                            'answer_set_id' => $setId,
                            'label' => $opt->label,
                            'value' => null,
                            'position' => $opt->position ?? 0,
                            'is_active' => (bool) $opt->is_active,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } else {
                        $itemId = $item->id;
                        DB::table('assessment_answer_set_items')->where('id', $itemId)->update([
                            'position' => $opt->position ?? 0,
                            'is_active' => (bool) $opt->is_active,
                            'updated_at' => $now,
                            'deleted_at' => null,
                        ]);
                    }
                    $optionMap[$opt->id] = $itemId;

                    $linkExists = DB::table('assessment_question_answer_links')
                        ->where('question_id', $question->id)
                        ->where('answer_set_item_id', $itemId)
                        ->exists();
                    if (!$linkExists) {
                        DB::table('assessment_question_answer_links')->insert([
                            'question_id' => $question->id,
                            'answer_set_item_id' => $itemId,
                            'position' => $opt->position ?? 0,
                            'is_active' => (bool) $opt->is_active,
                            'label_override' => null,
                            'value_override' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } else {
                        DB::table('assessment_question_answer_links')
                            ->where('question_id', $question->id)
                            ->where('answer_set_item_id', $itemId)
                            ->update([
                                'position' => $opt->position ?? 0,
                                'is_active' => (bool) $opt->is_active,
                                'deleted_at' => null,
                                'updated_at' => $now,
                            ]);
                    }
                    DB::table('assessment_question_options')
                        ->where('id', $opt->id)
                        ->update([
                            'answer_set_item_id' => $itemId,
                            'updated_at' => $now,
                        ]);
                }

                if (!empty($optionMap) && Schema::hasTable('assessment_answer_keys')) {
                    $keys = DB::table('assessment_answer_keys')->where('question_id', $question->id)->get();
                    foreach ($keys as $key) {
                        if (!empty($optionMap[$key->option_id ?? 0])) {
                            DB::table('assessment_answer_keys')
                                ->where('id', $key->id)
                                ->update([
                                    'answer_set_item_id' => $optionMap[$key->option_id],
                                ]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessment_answer_keys') && Schema::hasColumn('assessment_answer_keys', 'answer_set_item_id')) {
            Schema::table('assessment_answer_keys', function (Blueprint $table) {
                $table->dropIndex(['answer_set_item_id']);
                $table->dropColumn('answer_set_item_id');
            });
        }

        if (Schema::hasTable('assessment_exams')) {
            Schema::table('assessment_exams', function (Blueprint $table) {
                if (Schema::hasColumn('assessment_exams', 'version')) {
                    $table->dropColumn('version');
                }
                if (Schema::hasColumn('assessment_exams', 'origin_id')) {
                    $table->dropColumn('origin_id');
                }
                if (!Schema::hasColumn('assessment_exams', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->index();
                }
                if (!Schema::hasColumn('assessment_exams', 'version_int')) {
                    $table->unsignedInteger('version_int')->default(1);
                }
            });
        }

        if (Schema::hasTable('assessment_question_options') && Schema::hasColumn('assessment_question_options', 'answer_set_item_id')) {
            Schema::table('assessment_question_options', function (Blueprint $table) {
                $table->dropIndex('aqo_answer_set_item_idx');
                $table->dropColumn('answer_set_item_id');
            });
        }

        if (Schema::hasTable('assessment_questions')) {
            Schema::table('assessment_questions', function (Blueprint $table) {
                if (Schema::hasColumn('assessment_questions', 'version')) {
                    $table->dropColumn('version');
                }
                if (Schema::hasColumn('assessment_questions', 'origin_id')) {
                    $table->dropIndex(['origin_id']);
                    $table->dropColumn('origin_id');
                }
                if (Schema::hasColumn('assessment_questions', 'note_hint')) {
                    $table->dropColumn('note_hint');
                }
                if (Schema::hasColumn('assessment_questions', 'response_type')) {
                    $table->dropColumn('response_type');
                }
                if (!Schema::hasColumn('assessment_questions', 'selection_mode')) {
                    $table->enum('selection_mode', ['single', 'multiple'])->default('single');
                }
                if (!Schema::hasColumn('assessment_questions', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->index();
                }
                if (!Schema::hasColumn('assessment_questions', 'version_int')) {
                    $table->unsignedInteger('version_int')->default(1);
                }
            });
        }

        if (Schema::hasTable('answer_usage_aggregates') && Schema::hasColumn('answer_usage_aggregates', 'answer_set_item_id')) {
            Schema::table('answer_usage_aggregates', function (Blueprint $table) {
                $table->dropUnique(['answer_set_item_id']);
            });
            Schema::table('answer_usage_aggregates', function (Blueprint $table) {
                $table->renameColumn('answer_set_item_id', 'answer_id');
                $table->unique('answer_id');
            });
        }

        Schema::dropIfExists('assessment_attempt_text_answers');
        Schema::dropIfExists('assessment_question_response_parts');
        Schema::dropIfExists('assessment_question_answer_links');
        Schema::dropIfExists('assessment_answer_set_items');
        Schema::dropIfExists('assessment_answer_sets');
    }
};
        if (Schema::hasTable('answer_usage_aggregates') && Schema::hasColumn('answer_usage_aggregates', 'answer_id')) {
            Schema::table('answer_usage_aggregates', function (Blueprint $table) {
                $table->dropUnique(['answer_id']);
            });
            Schema::table('answer_usage_aggregates', function (Blueprint $table) {
                $table->renameColumn('answer_id', 'answer_set_item_id');
                $table->unique('answer_set_item_id');
            });
        }
