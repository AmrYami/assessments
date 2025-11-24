<?php

namespace Streaming\Assessments\Domain\Models;

use Streaming\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionWidget extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_question_widgets';

    protected $fillable = [
        'question_id','key','widget_type','position','is_active','required','placeholder','min','max','regex','eval_mode'
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
        'required' => 'boolean',
        'min' => 'integer',
        'max' => 'integer',
    ];
}

