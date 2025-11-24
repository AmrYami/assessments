<?php

namespace Streaming\Assessments\Services;

use Illuminate\Support\Facades\DB;
use Streaming\Assessments\Domain\Models\AnswerUsageAggregate;

class AnswerUsageService
{
    public function recalc(int $itemId): void
    {
        $questions = DB::table('assessment_question_answer_links')
            ->whereNull('deleted_at')
            ->where('answer_set_item_id', $itemId)
            ->pluck('question_id')
            ->unique()
            ->values()
            ->all();
        $qCount = count($questions);
        $pCount = 0;
        if ($qCount > 0) {
            $pCount = (int) DB::table('assessment_question_placements')
                ->whereIn('question_id', $questions)
                ->where('is_active', 1)
                ->where(function($q){ $q->whereNull('effective_at')->orWhere('effective_at','<=', now()); })
                ->count();
        }
        // Exam placements — definite (manual exams with explicit questions)
        $definite = 0; $potential = 0;
        if ($qCount > 0) {
            $manualExamIds = DB::table('assessment_exam_questions')->whereIn('question_id', $questions)->pluck('exam_id')->unique()->values()->all();
            if (!empty($manualExamIds)) {
                $definite = (int) DB::table('assessment_exam_placements as epl')
                    ->join('assessment_exams as e', 'e.id', '=', 'epl.exam_id')
                    ->whereIn('epl.exam_id', $manualExamIds)
                    ->where('e.status', '!=', 'archived')
                    ->where('epl.is_active', 1)
                    ->where(function($q){ $q->whereNull('epl.effective_at')->orWhere('epl.effective_at','<=', now()); })
                    ->count();
            }
            // Potential: dynamic exams whose placement scope includes any of the linked questions, and difficulty-aware if configured
            if (!config('assessments.potential_difficulty_aware')) {
                $potential = (int) DB::table('assessment_exam_placements as epl')
                    ->join('assessment_exams as e', 'e.id', '=', 'epl.exam_id')
                    ->whereIn('e.assembly_mode', ['by_count','by_score'])
                    ->where('e.status', '!=', 'archived')
                    ->where('epl.is_active', 1)
                    ->where(function($q){ $q->whereNull('epl.effective_at')->orWhere('epl.effective_at','<=', now()); })
                    ->whereExists(function($sub) use ($questions) {
                        $sub->select(DB::raw(1))
                            ->from('assessment_questions as q')
                            ->whereIn('q.id', $questions)
                            ->where('q.is_active', 1)
                            ->where(function($w){
                                $w->whereNull(DB::raw('epl.category_id'))
                                  ->orWhereExists(function($qq){
                                      $qq->select(DB::raw(1))
                                          ->from('assessment_question_categories as qc')
                                          ->whereColumn('qc.question_id', 'q.id')
                                          ->whereColumn('qc.category_id', 'epl.category_id');
                                  });
                            })
                            ->where(function($w){
                                $w->whereNull(DB::raw('epl.topic_id'))
                                  ->orWhereExists(function($qq){
                                      $qq->select(DB::raw(1))
                                          ->from('assessment_question_topics as qt')
                                          ->whereColumn('qt.question_id', 'q.id')
                                          ->whereColumn('qt.topic_id', 'epl.topic_id');
                                  });
                            })
                            ->where(function($w){
                                $w->whereNotExists(function($qq){ $qq->select(DB::raw(1))->from('assessment_exam_topics as et')->whereColumn('et.exam_id','epl.exam_id'); })
                                  ->orWhereExists(function($qq){
                                      $qq->select(DB::raw(1))
                                          ->from('assessment_exam_topics as et')
                                          ->join('assessment_question_topics as qt', 'qt.topic_id', '=', 'et.topic_id')
                                          ->whereColumn('et.exam_id', 'epl.exam_id')
                                          ->whereColumn('qt.question_id', 'q.id');
                                  });
                            });
                    })
                    ->count();
            } else {
                $placements = DB::table('assessment_exam_placements as epl')
                    ->join('assessment_exams as e', 'e.id', '=', 'epl.exam_id')
                    ->whereIn('e.assembly_mode', ['by_count','by_score'])
                    ->where('e.status', '!=', 'archived')
                    ->where('epl.is_active', 1)
                    ->where(function($q){ $q->whereNull('epl.effective_at')->orWhere('epl.effective_at','<=', now()); })
                    ->select('epl.exam_id','epl.category_id','epl.topic_id','e.assembly_mode','e.difficulty_split_json')
                    ->get();
                // Preload meta
                $qs = DB::table('assessment_questions')->whereIn('id',$questions)->get(['id','difficulty','weight','is_active'])->keyBy('id');
                $qc = DB::table('assessment_question_categories')->whereIn('question_id',$questions)->get()->groupBy('question_id');
                $qt = DB::table('assessment_question_topics')->whereIn('question_id',$questions)->get()->groupBy('question_id');
                $examIds = $placements->pluck('exam_id')->unique()->values()->all();
                $et = DB::table('assessment_exam_topics')->whereIn('exam_id',$examIds)->get()->groupBy('exam_id');
                $potential = 0;
                foreach ($placements as $p) {
                    $spl = json_decode($p->difficulty_split_json ?? 'null', true) ?: [];
                    $mode = $p->assembly_mode;
                    $split = $spl['splits'] ?? [];
                    if (empty($split)) continue;
                    $eligible = false;
                    foreach ($questions as $qid) {
                        $qr = $qs->get($qid); if (!$qr || !$qr->is_active) continue;
                        // Placement category/topic
                        if ($p->category_id) {
                            $catOk = ($qc[$qid] ?? collect())->contains(fn($row)=> (int)$row->category_id === (int)$p->category_id);
                            if (!$catOk) continue;
                        }
                        if ($p->topic_id) {
                            $topOk = ($qt[$qid] ?? collect())->contains(fn($row)=> (int)$row->topic_id === (int)$p->topic_id);
                            if (!$topOk) continue;
                        }
                        // Exam topics intersection if exam has topics
                        if (($et[$p->exam_id] ?? collect())->isNotEmpty()) {
                            $qTopics = ($qt[$qid] ?? collect())->pluck('topic_id')->map(fn($v)=>(int)$v)->all();
                            $eTopics = ($et[$p->exam_id] ?? collect())->pluck('topic_id')->map(fn($v)=>(int)$v)->all();
                            if (count(array_intersect($qTopics, $eTopics)) === 0) continue;
                        }
                        $diff = (string)$qr->difficulty;
                        $slot = (int)($split[$diff] ?? 0);
                        if ($slot <= 0) continue;
                        if ($mode === 'by_score') {
                            if ((int)$qr->weight > $slot) continue; // weight must be <= split slot
                        }
                        $eligible = true; break;
                    }
                    if ($eligible) $potential++;
                }
            }
        }

        AnswerUsageAggregate::updateOrCreate(
            ['answer_set_item_id' => $itemId],
            [
                'used_by_questions_count' => $qCount,
                'used_by_placements_count' => $pCount,
                'used_by_exam_placements_definite' => $definite,
                'used_by_exam_placements_potential' => $potential,
                'last_recomputed_at' => now(),
            ]
        );
    }

    public function bumpAttempts(array $itemIds): void
    {
        if (empty($itemIds)) return;
        $now = now();
        foreach (array_unique(array_map('intval', $itemIds)) as $itemId) {
            $row = AnswerUsageAggregate::firstOrNew(['answer_set_item_id' => $itemId]);
            $row->used_by_attempts_count = (int)($row->used_by_attempts_count ?? 0) + 1;
            $row->last_used_at = $now;
            $row->save();
        }
    }
}
