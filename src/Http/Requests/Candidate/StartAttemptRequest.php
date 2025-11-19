<?php

namespace Fakeeh\Assessments\Http\Requests\Candidate;

use Fakeeh\Assessments\Support\FormRequest;

class StartAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seed' => 'nullable|integer|min:1',
        ];
    }

    public function seed(): ?int
    {
        $validated = $this->validated();

        return array_key_exists('seed', $validated)
            ? (int) $validated['seed']
            : null;
    }
}
