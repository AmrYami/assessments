<?php

namespace Yami\Assessments\Http\Requests\Admin\Api;

use Yami\Assessments\Support\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnswerSetRequest extends FormRequest
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
                Rule::unique('assessment_answer_sets', 'slug'),
            ],
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.label' => 'required|string|max:255',
            'items.*.value' => 'nullable|string|max:255',
        ];
    }
}
