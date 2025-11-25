<?php

namespace Yami\Assessments\Http\Requests\Admin;

use Yami\Assessments\Support\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnswerSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_answer_sets', 'slug'),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.label' => 'required|string|max:255',
            'items.*.value' => 'nullable|string|max:255',
            'items.*.is_active' => 'boolean',
        ];
    }
}
