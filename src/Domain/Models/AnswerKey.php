<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;

class AnswerKey extends BaseModel
{
    protected $table = 'assessment_answer_keys';

    protected $fillable = [
        'question_id',
        'option_id',
        'answer_set_item_id',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'option_id');
    }

    public function item()
    {
        return $this->belongsTo(AnswerSetItem::class, 'answer_set_item_id');
    }
}
