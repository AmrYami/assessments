<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswerSetItem extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_answer_set_items';

    protected $fillable = [
        'answer_set_id',
        'label',
        'value',
        'position',
        'is_active',
    ];

    protected $casts = [
        'answer_set_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function set()
    {
        return $this->belongsTo(AnswerSet::class, 'answer_set_id');
    }

    public function questionLinks()
    {
        return $this->hasMany(QuestionAnswer::class, 'answer_set_item_id');
    }
}
