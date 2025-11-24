<?php

namespace Amryami\Assessments\Domain\Models;

use Amryami\Assessments\Support\BaseModel;

class AnswerUsageAggregate extends BaseModel
{
    protected $table = 'answer_usage_aggregates';

    protected $fillable = [
        'answer_set_item_id', 'used_by_questions_count', 'used_by_placements_count', 'used_by_attempts_count',
        'used_by_exam_placements_definite', 'used_by_exam_placements_potential',
        'last_used_at', 'last_recomputed_at',
    ];

    protected $casts = [
        'used_by_questions_count' => 'integer',
        'used_by_placements_count' => 'integer',
        'used_by_attempts_count' => 'integer',
        'last_used_at' => 'datetime',
        'used_by_exam_placements_definite' => 'integer',
        'used_by_exam_placements_potential' => 'integer',
        'last_recomputed_at' => 'datetime',
    ];
}
