<?php

namespace Streaming\Assessments\Domain\Models;

use Streaming\Assessments\Support\BaseModel;

class Attempt extends BaseModel
{
    protected $table = 'assessment_attempts';

    protected $fillable = [
        'exam_id',
        'user_id',
        'status',
        'started_at',
        'expires_at',
        'seed',
        'frozen_question_ids',
        'total_score',
        'percent',
        'passed',
        'result_json',
        'score_auto',
        'score_manual',
        'review_status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'frozen_question_ids' => 'array',
        'total_score' => 'integer',
        'percent' => 'integer',
        'passed' => 'boolean',
        'result_json' => 'array',
        'score_auto' => 'integer',
        'score_manual' => 'integer',
    ];
}
