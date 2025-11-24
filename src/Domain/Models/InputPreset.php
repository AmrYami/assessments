<?php

namespace Streaming\Assessments\Domain\Models;

use Streaming\Assessments\Support\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputPreset extends BaseModel
{
    use SoftDeletes;

    protected $table = 'assessment_input_presets';

    protected $fillable = [
        'slug', 'label', 'input_type', 'spec_json', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'spec_json' => 'array',
    ];
}

