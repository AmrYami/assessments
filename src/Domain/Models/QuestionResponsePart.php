<?php

namespace Fakeeh\Assessments\Domain\Models;

use Fakeeh\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionResponsePart extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_question_response_parts';

    protected $fillable = [
        'question_id',
        'key',
        'label',
        'input_type',
        'required',
        'validation_mode',
        'validation_value',
        'weight_share',
        'position',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'required' => 'boolean',
        'weight_share' => 'integer',
        'position' => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
