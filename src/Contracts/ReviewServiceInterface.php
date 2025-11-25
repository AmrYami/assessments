<?php

namespace Amryami\Assessments\Contracts;

use Amryami\Assessments\Domain\Models\Attempt;

interface ReviewServiceInterface
{
    /**
     * Apply manual scoring for an attempt.
     *
     * @param  Attempt  $attempt  Attempt being reviewed
     * @param  array  $items  Array of ['question_id' => int, 'awarded_score' => int]
     * @param  bool  $finalize  Whether to finalize the review
     * @param  string|null  $notes  Reviewer notes
     * @param  int|null  $reviewerId  Reviewer user id
     *
     * @return Attempt Updated attempt instance
     */
    public function apply(Attempt $attempt, array $items, bool $finalize = false, ?string $notes = null, ?int $reviewerId = null): Attempt;
}

