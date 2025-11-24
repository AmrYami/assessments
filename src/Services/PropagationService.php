<?php

namespace Streaming\Assessments\Services;

use Streaming\Assessments\Domain\Models\{Question, Exam, QuestionPlacement, ExamPlacement, Topic, QuestionOption, AnswerKey, QuestionAnswer, QuestionResponsePart};
use Streaming\Assessments\Support\ModelResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropagationService
{
    public function propagateQuestion(Question $question, array $applyTo, string $mode, $effectiveAt = null): array
    {
        $categoryClass = ModelResolver::category();
        $categories = $this->normalizeScope($applyTo['categories'] ?? null, $categoryClass);
        $topics = $this->normalizeScope($applyTo['topics'] ?? null, Topic::class);
        if (empty($categories) && empty($topics)) {
            return ['updated' => 0, 'mode' => $mode, 'note' => 'No scopes selected'];
        }
        $count = 0;
        if ($mode === 'bump_placement') {
            $catList = $categories ?: [null];
            $topList = $topics ?: [null];
            foreach ($catList as $catId) {
                foreach ($topList as $topId) {
                    if ($catId === null && $topId === null) continue; // invariant
                    $placement = QuestionPlacement::firstOrCreate(
                        ['question_id'=>$question->id, 'category_id'=>$catId, 'topic_id'=>$topId],
                        ['placement_version'=>1, 'is_active'=>true]
                    );
                    $placement->placement_version = (int)$placement->placement_version + 1;
                    $placement->effective_at = $effectiveAt ? \Carbon\Carbon::parse($effectiveAt) : $placement->effective_at;
                    $placement->save();
                    $count++;
                }
            }
            return ['updated' => $count, 'mode' => $mode];
        }
        // clone_and_remap — create a new Question record and rebind selected scopes
        return DB::transaction(function () use ($question, $categories, $topics, $effectiveAt) {
            // 1) Clone base question
            $new = $question->replicate(['slug']); // we'll set slug below
            $new->slug = $this->uniqueSlug($question->slug, 'assessment_questions', 'slug');
            $originId = $question->origin_id ?: $question->id;
            $new->origin_id = $originId;
            $maxV = Question::query()->where('origin_id', $originId)->max('version');
            $new->version = ((int)$maxV ?: 1) + 1;
            $new->save();
            try { $new->schema_hash = app(\Streaming\Assessments\Services\SchemaHashService::class)->computeForQuestion($new); $new->save(); } catch (\Throwable $e) {}

            // 2) Clone options maintaining order
            $oldOptions = QuestionOption::where('question_id', $question->id)->orderBy('position')->get();
            $optionIdMap = [];
            foreach ($oldOptions as $opt) {
                $copy = $opt->replicate();
                $copy->question_id = $new->id;
                $copy->save();
                $optionIdMap[$opt->id] = $copy->id;
            }
            // 3) Clone answer keys using id map
            $keys = AnswerKey::where('question_id', $question->id)->get();
            foreach ($keys as $k) {
                $oid = $optionIdMap[$k->option_id] ?? null;
                if ($oid) {
                    AnswerKey::create([
                        'question_id' => $new->id,
                        'option_id' => $oid,
                        'answer_set_item_id' => $k->answer_set_item_id,
                    ]);
                }
            }
            // 4) Clone answers library links
            $links = QuestionAnswer::withTrashed()->where('question_id', $question->id)->get();
            foreach ($links as $lnk) {
                $copy = $lnk->replicate();
                $copy->question_id = $new->id;
                $copy->deleted_at = null;
                $copy->save();
            }
            // 5) Clone response parts
            $parts = QuestionResponsePart::where('question_id', $question->id)->orderBy('position')->get();
            foreach ($parts as $part) {
                $partCopy = $part->replicate();
                $partCopy->question_id = $new->id;
                $partCopy->save();
            }
            // 6) Clone topic/category pivots (full), then remap selected
            $oldTopicIds = DB::table('assessment_question_topics')->where('question_id',$question->id)->pluck('topic_id')->all();
            $oldCatIds = DB::table('assessment_question_categories')->where('question_id',$question->id)->pluck('category_id')->all();
            foreach ($oldTopicIds as $tid) {
                DB::table('assessment_question_topics')->insertOrIgnore(['question_id'=>$new->id,'topic_id'=>$tid,'created_at'=>now(),'updated_at'=>now()]);
            }
            foreach ($oldCatIds as $cid) {
                DB::table('assessment_question_categories')->insertOrIgnore(['question_id'=>$new->id,'category_id'=>$cid,'created_at'=>now(),'updated_at'=>now()]);
            }
            // 6) Apply scoped remap: move selected categories/topics from old to new
            if (!empty($categories)) {
                // Ensure on new, remove from old
                DB::table('assessment_question_categories')->where('question_id',$question->id)->whereIn('category_id',$categories)->delete();
                foreach ($categories as $cid) {
                    DB::table('assessment_question_categories')->updateOrInsert(['question_id'=>$new->id,'category_id'=>$cid], ['updated_at'=>now(),'created_at'=>now()]);
                }
            }
            if (!empty($topics)) {
                DB::table('assessment_question_topics')->where('question_id',$question->id)->whereIn('topic_id',$topics)->delete();
                foreach ($topics as $tid) {
                    DB::table('assessment_question_topics')->updateOrInsert(['question_id'=>$new->id,'topic_id'=>$tid], ['updated_at'=>now(),'created_at'=>now()]);
                }
            }
            // 7) Placements bookkeeping: mark new as active for selected scopes; add sentinel on old
            $catList = $categories ?: [null];
            $topList = $topics ?: [null];
            $updated = 0;
            foreach ($catList as $catId) {
                foreach ($topList as $topId) {
                    if ($catId === null && $topId === null) continue;
                    $placement = QuestionPlacement::firstOrCreate(
                        ['question_id'=>$new->id, 'category_id'=>$catId, 'topic_id'=>$topId],
                        ['placement_version'=>1, 'is_active'=>true]
                    );
                    $placement->placement_version = (int)$placement->placement_version + 1;
                    $placement->effective_at = $effectiveAt ? \Carbon\Carbon::parse($effectiveAt) : $placement->effective_at;
                    $placement->save();
                    $updated++;
                }
            }
            // sentinel placement on old (prevents fallback logic if introduced later)
            QuestionPlacement::firstOrCreate(['question_id'=>$question->id, 'category_id'=>null, 'topic_id'=>null], ['placement_version'=>1,'is_active'=>true]);
            return ['updated' => $updated, 'mode' => 'clone_and_remap', 'new_question_id' => $new->id];
        });
    }

    public function propagateExam(Exam $exam, array $applyTo, string $mode, $effectiveAt = null): array
    {
        $categoryClass = ModelResolver::category();
        $categories = $this->normalizeScope($applyTo['categories'] ?? null, $categoryClass);
        $topics = $this->normalizeScope($applyTo['topics'] ?? null, Topic::class);
        if (empty($categories) && empty($topics)) {
            return ['updated' => 0, 'mode' => $mode, 'note' => 'No scopes selected'];
        }
        $count = 0;
        if ($mode === 'bump_placement') {
            $catList = $categories ?: [null];
            $topList = $topics ?: [null];
            foreach ($catList as $catId) {
                foreach ($topList as $topId) {
                    if ($catId === null && $topId === null) continue;
                    $placement = ExamPlacement::firstOrCreate(
                        ['exam_id'=>$exam->id, 'category_id'=>$catId, 'topic_id'=>$topId],
                        ['placement_version'=>1, 'is_active'=>true]
                    );
                    $placement->placement_version = (int)$placement->placement_version + 1;
                    $placement->effective_at = $effectiveAt ? \Carbon\Carbon::parse($effectiveAt) : $placement->effective_at;
                    $placement->save();
                    $count++;
                }
            }
            return ['updated' => $count, 'mode' => $mode];
        }
        // clone_and_remap — clone exam, copy relations, create placements for selected scopes, add sentinel to old
        return DB::transaction(function () use ($exam, $categories, $topics, $effectiveAt) {
            // 1) Clone base exam
            $new = $exam->replicate(['slug']);
            $new->slug = $this->uniqueSlug($exam->slug, 'assessment_exams', 'slug');
            $originId = $exam->origin_id ?: $exam->id;
            $new->origin_id = $originId;
            $maxV = Exam::query()->where('origin_id', $originId)->max('version');
            $new->version = ((int)$maxV ?: 1) + 1;
            $new->save();
            try { $new->schema_hash = app(\Streaming\Assessments\Services\SchemaHashService::class)->computeForExam($new); $new->save(); } catch (\Throwable $e) {}
            // 2) Clone topics
            $topicIds = DB::table('assessment_exam_topics')->where('exam_id',$exam->id)->pluck('topic_id')->all();
            foreach ($topicIds as $tid) {
                DB::table('assessment_exam_topics')->insertOrIgnore(['exam_id'=>$new->id,'topic_id'=>$tid,'created_at'=>now(),'updated_at'=>now()]);
            }
            // 3) Clone manual questions pivot if manual
            if ($exam->assembly_mode === 'manual') {
                $pivots = DB::table('assessment_exam_questions')->where('exam_id',$exam->id)->orderBy('position')->get();
                foreach ($pivots as $pv) {
                    DB::table('assessment_exam_questions')->insert([
                        'exam_id' => $new->id,
                        'question_id' => $pv->question_id,
                        'position' => $pv->position,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            // 4) Placements: assign selected scopes to new
            $updated = 0;
            $catList = $categories ?: [null];
            $topList = $topics ?: [null];
            foreach ($catList as $catId) {
                foreach ($topList as $topId) {
                    if ($catId === null && $topId === null) continue;
                    $placement = ExamPlacement::firstOrCreate(
                        ['exam_id'=>$new->id, 'category_id'=>$catId, 'topic_id'=>$topId],
                        ['placement_version'=>1, 'is_active'=>true]
                    );
                    $placement->placement_version = (int)$placement->placement_version + 1;
                    $placement->effective_at = $effectiveAt ? \Carbon\Carbon::parse($effectiveAt) : $placement->effective_at;
                    $placement->save();
                    $updated++;
                }
            }
            // 5) Sentinel on old to opt-out from fallback listings
            ExamPlacement::firstOrCreate(['exam_id'=>$exam->id, 'category_id'=>null, 'topic_id'=>null], ['placement_version'=>1,'is_active'=>true]);
            return ['updated' => $updated, 'mode' => 'clone_and_remap', 'new_exam_id' => $new->id];
        });
    }

    protected function normalizeScope($value, string $modelClass): array
    {
        if ($value === 'all') {
            return $modelClass::query()->pluck('id')->map(fn($v)=>(int)$v)->all();
        }
        if (is_array($value)) {
            return array_values(array_map('intval', $value));
        }
        return [];
    }

    protected function uniqueSlug(string $base, string $table, string $column = 'slug'): string
    {
        $slug = Str::slug($base);
        if (!DB::table($table)->where($column, $slug)->exists()) return $slug;
        $i = 2;
        while (true) {
            $try = Str::limit($slug, 240, '') . '-v' . $i;
            if (!DB::table($table)->where($column, $try)->exists()) return $try;
            $i++;
        }
    }
}
