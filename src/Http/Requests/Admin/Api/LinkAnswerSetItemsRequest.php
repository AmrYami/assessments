<?php

namespace Yami\Assessments\Http\Requests\Admin\Api;

use Yami\Assessments\Support\FormRequest;

class LinkAnswerSetItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:assessment_answer_set_items,id',
            'items.*.is_active' => 'boolean',
            'items.*.label_override' => 'nullable|string|max:255',
            'items.*.value_override' => 'nullable|string|max:255',
            'items.*.is_correct' => 'boolean',
        ];
    }
}
