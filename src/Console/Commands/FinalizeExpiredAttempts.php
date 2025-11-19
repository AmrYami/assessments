<?php

namespace Fakeeh\Assessments\Console\Commands;

use Fakeeh\Assessments\Domain\Models\{AnswerKey, Attempt, AttemptTextAnswer, Exam, Question};
use Illuminate\Console\Command;

class FinalizeExpiredAttempts extends Command
{
    protected $signature = 'assessments:finalize-expired';

    protected $description = 'Finalize and grade expired in-progress assessment attempts';

    public function handle(): int
    {
        $now = now();
        $grace = (int) config('assessments.assembly.grace_seconds', 5);
        $attempts = Attempt::where('status', 'in_progress')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now->copy()->subSeconds($grace))
            ->get();
        $count = 0;
        foreach ($attempts as $attempt) {
            $frozen = $attempt->frozen_question_ids ?? [];
            $answers = \DB::table('assessment_attempt_answers')
                ->where('attempt_id', $attempt->id)
                ->get()
                ->keyBy('question_id');
            $questions = Question::whereIn('id', $frozen)
                ->with(['responseParts'])
                ->get()
                ->keyBy('id');
            $textAnswers = AttemptTextAnswer::where('attempt_id', $attempt->id)
                ->get()
                ->groupBy('question_id');

            $totalPossible = 0;
            $score = 0;
            $details = [];
            $needsReview = false;
            foreach ($frozen as $qid) {
                $q = $questions[$qid];
                $totalPossible += (int) $q->weight;
                if (in_array($q->response_type, ['single_choice', 'multiple_choice'], true)) {
                    $selected = collect(json_decode(optional($answers->get($qid))->option_ids ?? '[]', true))
                        ->map(fn ($v) => (int) $v)
                        ->all();
                    $correct = AnswerKey::where('question_id', $qid)
                        ->pluck('option_id')
                        ->map(fn ($v) => (int) $v)
                        ->all();
                    sort($selected);
                    sort($correct);
                    $isCorrect = $q->response_type === 'single_choice'
                        ? (count($selected) === 1 && count($correct) === 1 && $selected[0] === $correct[0])
                        : ($selected === $correct);
                    if ($isCorrect) {
                        $score += (int) $q->weight;
                    }
                    $details[] = [
                        'question_id' => $qid,
                        'response_type' => $q->response_type,
                        'selected' => $selected,
                        'correct' => $isCorrect,
                        'weight' => (int) $q->weight,
                    ];
                } else {
                    $needsReview = true;
                    $answersForQuestion = $textAnswers->get($qid) ?? collect();
                    $map = $answersForQuestion->keyBy(fn (AttemptTextAnswer $a) => $a->part_key);
                    $textParts = $q->responseParts
                        ->map(function ($part) use ($map) {
                            return [
                                'key' => $part->key,
                                'label' => $part->label,
                                'value' => optional($map->get($part->key))->text_value,
                            ];
                        })
                        ->all();
                    $note = optional($map->get('__note__'))->text_value;
                    $details[] = [
                        'question_id' => $qid,
                        'response_type' => $q->response_type,
                        'text_parts' => $textParts,
                        'weight' => (int) $q->weight,
                        'note' => $note,
                    ];
                }
            }
            $percent = $totalPossible > 0 ? (int) floor(($score / $totalPossible) * 100) : 0;
            $exam = Exam::find($attempt->exam_id);
            $passed = $exam && ($exam->pass_type === 'score'
                ? $score >= (int) $exam->pass_value
                : $percent >= (int) $exam->pass_value);

            $attempt->update([
                'status' => 'submitted',
                'total_score' => $score,
                'score_auto' => $score,
                'score_manual' => 0,
                'review_status' => $needsReview ? 'pending' : 'not_needed',
                'percent' => $percent,
                'passed' => $passed,
                'result_json' => [
                    'details' => $details,
                    'total_possible' => $totalPossible,
                    'auto_finalized' => true,
                ],
            ]);
            $count++;
        }
        $this->info("Finalized {$count} attempts.");
        return self::SUCCESS;
    }
}
