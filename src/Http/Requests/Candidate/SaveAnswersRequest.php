<?php

namespace Streaming\Assessments\Http\Requests\Candidate;

use Streaming\Assessments\Support\FormRequest;

class SaveAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => 'array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.option_ids' => 'array',
            'answers.*.option_ids.*' => 'integer',
            'answers.*.parts' => 'array',
            'answers.*.parts.*.key' => 'nullable|string',
            'answers.*.parts.*.text' => 'nullable|string',
            'answers.*.parts.*.value' => 'nullable|string',
            'answers.*.text' => 'nullable|string',
            'answers.*.note' => 'nullable|string',
            'answers.*.note_text' => 'nullable|string',
        ];
    }

    public function answers(): array
    {
        $validated = $this->validated();

        return array_values($validated['answers'] ?? []);
    }
}
