<?php

namespace Yami\Assessments\Http\Requests\Admin;

use Yami\Assessments\Support\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_topics', 'slug'),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'position' => 'nullable|integer',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
