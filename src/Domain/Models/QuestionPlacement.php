<?php

namespace Streaming\Assessments\Domain\Models;

use Streaming\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionPlacement extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_question_placements';

    protected $fillable = [
        'question_id', 'category_id', 'topic_id', 'placement_version', 'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'topic_id' => 'integer',
        'placement_version' => 'integer',
        'is_active' => 'boolean',
    ];
}

