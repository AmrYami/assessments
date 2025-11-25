<?php

namespace Amryami\Assessments\Http\Requests\Admin;

use Amryami\Assessments\Support\FormRequest;

class PreviewPropagationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'apply_to' => 'required|array',
            'apply_to.categories' => 'nullable',
            'apply_to.topics' => 'nullable',
            'mode' => 'nullable|in:bump_placement,clone_and_remap',
            'effective_at' => 'nullable|date',
        ];
    }
}
