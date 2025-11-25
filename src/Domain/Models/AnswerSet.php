<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnswerSet extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_answer_sets';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(AnswerSetItem::class, 'answer_set_id')->orderBy('position');
    }
}
