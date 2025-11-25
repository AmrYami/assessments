<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttemptTextAnswer extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_attempt_text_answers';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'part_key',
        'text_value',
        'is_valid',
        'score_awarded',
    ];

    protected $casts = [
        'attempt_id' => 'integer',
        'question_id' => 'integer',
        'is_valid' => 'boolean',
        'score_awarded' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
