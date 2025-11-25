<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Support\BaseModel;
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

