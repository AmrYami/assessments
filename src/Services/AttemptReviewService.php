<?php

namespace Fakeeh\Assessments\Services;

use Fakeeh\Assessments\Contracts\ReviewServiceInterface;
use Fakeeh\Assessments\Domain\Models\{Attempt, Exam, ExamRequirement};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AttemptReviewService implements ReviewServiceInterface
{
    public function apply(Attempt $attempt, array $items, bool $finalize = false, ?string $notes = null, ?int $reviewerId = null): Attempt
    {
        return DB::transaction(function () use ($attempt, $items, $finalize, $notes, $reviewerId) {
            $manualTotal = 0;

            foreach ($items as $row) {
                $questionId = (int) Arr::get($row, 'question_id');
                $awarded = max(0, (int) Arr::get($row, 'awarded_score', 0));

                DB::table('assessment_attempt_answers')->updateOrInsert(
                    ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                    [
                        'awarded_score' => $awarded,
                        'needs_review' => 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $manualTotal += $awarded;
            }

            $attempt->score_manual = $manualTotal;
            $attempt->total_score = (int) ($attempt->score_auto ?? 0) + $manualTotal;
            $maxScore = (int) ($attempt->score_max ?? 0);
            $attempt->percent = $maxScore > 0
                ? (int) floor(($attempt->total_score / $maxScore) * 100)
                : (int) $attempt->percent;

            if ($finalize) {
                $attempt->review_status = 'completed';
                $attempt->reviewed_at = now();
                $attempt->reviewer_id = $reviewerId;
                if ($notes !== null) {
                    $attempt->review_notes = $notes;
                }
                $attempt->passed = $this->didPass($attempt, $attempt->total_score, $attempt->percent);
                $this->updateRequirementStatus($attempt);
            } else {
                $attempt->review_status = 'in_review';
                if ($notes !== null) {
                    $attempt->review_notes = $notes;
                }
            }

            $attempt->save();

            return $attempt;
        });
    }

    protected function didPass(Attempt $attempt, int $score, int $percent): bool
    {
        $exam = Exam::findOrFail($attempt->exam_id);
        if ($exam->pass_type === 'score') {
            return $score >= (int) $exam->pass_value;
        }

        return $percent >= (int) $exam->pass_value;
    }

    protected function updateRequirementStatus(Attempt $attempt): void
    {
        /** @var ExamRequirement|null $requirement */
        $requirement = ExamRequirement::where('user_id', $attempt->user_id)
            ->where('exam_id', $attempt->exam_id)
            ->first();

        if ($requirement) {
            $requirement->status = $attempt->passed ? 'passed' : 'failed';
            $requirement->last_attempt_id = $attempt->id;
            $requirement->save();
        }
    }
}

