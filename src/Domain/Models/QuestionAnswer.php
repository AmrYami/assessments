<?php

namespace Streaming\Assessments\Domain\Models;

use Streaming\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionAnswer extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_question_answer_links';

    protected $fillable = [
        'question_id',
        'answer_set_item_id',
        'position',
        'is_active',
        'is_correct',
        'label_override',
        'value_override',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
        'is_correct' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(AnswerSetItem::class, 'answer_set_item_id');
    }
}
