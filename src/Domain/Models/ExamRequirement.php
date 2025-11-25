<?php

namespace Amryami\Assessments\Domain\Models;

use Amryami\Assessments\Support\BaseModel;

class ExamRequirement extends BaseModel
{
    protected $table = 'assessment_exam_requirements';

    protected $fillable = [
        'user_id','exam_id','status','attempts_used','max_attempts','last_attempt_id','assigned_at','fail_action'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];
}

