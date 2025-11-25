<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;

class QuestionOption extends BaseModel
{
    protected $table = 'assessment_question_options';

    protected $fillable = [
        'question_id',
        'answer_set_item_id',
        'label',
        'key',
        'position',
        'is_active'
    ];

    protected $casts = [
        'answer_set_item_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
