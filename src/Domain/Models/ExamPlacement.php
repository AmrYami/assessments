<?php

namespace Amryami\Assessments\Domain\Models;

use Amryami\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamPlacement extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_exam_placements';

    protected $fillable = [
        'exam_id', 'category_id', 'topic_id', 'placement_version', 'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'topic_id' => 'integer',
        'placement_version' => 'integer',
        'is_active' => 'boolean',
    ];
}

