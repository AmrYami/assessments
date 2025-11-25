<?php

namespace Yami\Assessments\Http\Requests\Admin;

use Yami\Assessments\Support\FormRequest;

class PropagateRequest extends FormRequest
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
            'mode' => 'required|in:bump_placement,clone_and_remap',
            'confirm_global' => 'nullable|boolean',
            'effective_at' => 'nullable|date',
        ];
    }

    protected function passedValidation(): void
    {
        if (!config('assessments.propagation_strict')) {
            return;
        }

        $apply = (array) $this->input('apply_to', []);
        $allCats = ($apply['categories'] ?? null) === 'all';
        $allTops = ($apply['topics'] ?? null) === 'all';

        if ($allCats && $allTops && !$this->boolean('confirm_global')) {
            $this->getValidatorInstance()->errors()->add('confirm_global', 'Global propagation requires confirmation.');
            throw new \Illuminate\Validation\ValidationException($this->getValidatorInstance());
        }
    }
}
