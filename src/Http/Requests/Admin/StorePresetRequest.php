<?php

namespace Yami\Assessments\Http\Requests\Admin;

use Yami\Assessments\Support\FormRequest;
use Illuminate\Validation\Rule;

class StorePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_input_presets', 'slug'),
            ],
            'label' => 'required|string|max:255',
            'input_type' => 'required|in:text,textarea',
            'spec_json' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
