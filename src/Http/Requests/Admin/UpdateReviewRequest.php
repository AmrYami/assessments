<?php

namespace Yami\Assessments\Http\Requests\Admin;

use Yami\Assessments\Support\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.question_id' => 'required|integer',
            'items.*.awarded_score' => 'required|integer|min:0',
            'finalize' => 'boolean',
            'review_notes' => 'nullable|string',
        ];
    }
}
