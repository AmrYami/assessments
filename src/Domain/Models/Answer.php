<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_answers';

    protected $fillable = [
        'slug', 'label', 'kind', 'input_type', 'validation_json', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'validation_json' => 'array',
    ];
}

