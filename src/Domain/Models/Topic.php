<?php

namespace Amryami\Assessments\Domain\Models;

use Amryami\Assessments\Support\BaseModel;

class Topic extends BaseModel
{
    protected $table = 'assessment_topics';

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'assessment_question_topics', 'topic_id', 'question_id');
    }
}

