<?php

namespace Amryami\Assessments\Http\Requests\Admin;

use Amryami\Assessments\Domain\Models\Question;
use Amryami\Assessments\Support\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $question = $this->route('question');
        $ignoreId = $question instanceof Question ? $question->getKey() : null;

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_questions', 'slug')->ignore($ignoreId),
            ],
            'text' => 'required|string',
            'response_type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'text', 'textarea'])],
            'weight' => 'required|integer|min:1',
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard', 'very_hard'])],
            'is_active' => 'boolean',
            'note_enabled' => 'boolean',
            'note_required' => 'boolean',
            'note_hint' => 'nullable|string|max:255',
            'explanation' => 'nullable|string',
            'topics' => 'array',
            'topics.*' => 'integer|exists:assessment_topics,id',
            'answer_set_id' => 'nullable|integer|exists:assessment_answer_sets,id',
            'max_choices' => 'nullable|integer|min:1',
            'answer_links' => 'array',
            'answer_links.*.answer_set_item_id' => 'nullable|integer|exists:assessment_answer_set_items,id',
            'answer_links.*.label' => 'nullable|string|max:255',
            'answer_links.*.value' => 'nullable|string|max:255',
            'answer_links.*.label_override' => 'nullable|string|max:255',
            'answer_links.*.value_override' => 'nullable|string|max:255',
            'answer_links.*.is_active' => 'boolean',
            'answer_links.*.is_correct' => 'boolean',
            'answer_links.*.position' => 'nullable|integer|min:0',
            'response_parts' => 'array',
            'response_parts.*.key' => 'required_with:response_parts|string|max:64',
            'response_parts.*.label' => 'required_with:response_parts|string|max:255',
            'response_parts.*.input_type' => 'nullable|in:text,textarea',
            'response_parts.*.required' => 'boolean',
            'response_parts.*.validation_mode' => 'nullable|in:none,exact,regex',
            'response_parts.*.validation_value' => 'nullable|string',
            'response_parts.*.weight_share' => 'nullable|integer|min:0',
        ];
    }

    public function validatedPayload(?Question $existing = null): array
    {
        $data = $this->validated();
        $responseType = $data['response_type'];
        $isChoice = in_array($responseType, ['single_choice', 'multiple_choice'], true);

        if (!$isChoice) {
            $data['answer_links'] = $data['answer_links'] ?? [];
        }

        if (($data['note_required'] ?? false) && !($data['note_enabled'] ?? false)) {
            throw ValidationException::withMessages([
                'note_required' => 'Note cannot be required unless note is enabled.',
            ]);
        }

        if ($isChoice) {
            $links = array_values($data['answer_links'] ?? []);
            if (count($links) < 2) {
                throw ValidationException::withMessages([
                    'answer_links' => 'Provide at least two answer options for choice-based questions.',
                ]);
            }
            foreach ($links as $index => $link) {
                if (empty($link['answer_set_item_id']) && !isset($link['label'])) {
                    throw ValidationException::withMessages([
                        "answer_links.$index.label" => 'Provide a label for new answer items.',
                    ]);
                }
            }

            $correctCount = collect($links)->filter(fn($link) => !empty($link['is_correct']))->count();
            if ($responseType === 'single_choice' && $correctCount !== 1) {
                throw ValidationException::withMessages([
                    'answer_links' => 'Single-choice questions must have exactly one correct answer.',
                ]);
            }
            if ($responseType === 'multiple_choice' && $correctCount < 1) {
                throw ValidationException::withMessages([
                    'answer_links' => 'Multiple-choice questions must have at least one correct answer.',
                ]);
            }
            if ($responseType === 'multiple_choice' && !empty($data['max_choices'])) {
                $activeCount = collect($links)->filter(function ($link) {
                    return !array_key_exists('is_active', $link) || $link['is_active'];
                })->count();
                if ($data['max_choices'] > $activeCount) {
                    throw ValidationException::withMessages([
                        'max_choices' => "Max choices cannot exceed the number of active options ({$activeCount}).",
                    ]);
                }
            }
            $data['answer_links'] = $links;
        } else {
            $parts = array_values($data['response_parts'] ?? []);
            if (empty($parts)) {
                throw ValidationException::withMessages([
                    'response_parts' => 'Provide at least one response part for text-based questions.',
                ]);
            }
            $keyCheck = [];
            foreach ($parts as $index => $part) {
                $keyLower = Str::lower($part['key']);
                if (isset($keyCheck[$keyLower])) {
                    throw ValidationException::withMessages([
                        "response_parts.$index.key" => 'Response part keys must be unique per question.',
                    ]);
                }
                $keyCheck[$keyLower] = true;
                $mode = $part['validation_mode'] ?? 'none';
                if (in_array($mode, ['exact', 'regex'], true) && empty($part['validation_value'])) {
                    throw ValidationException::withMessages([
                        "response_parts.$index.validation_value" => 'Provide a validation value for exact/regex modes.',
                    ]);
                }
            }
            $data['response_parts'] = $parts;
        }

        if ($responseType !== 'multiple_choice') {
            $data['max_choices'] = null;
        }

        return $data;
    }
}
