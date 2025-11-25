<?php

namespace Amryami\Assessments\Http\Requests\Admin;

use Amryami\Assessments\Support\FormRequest;
use Illuminate\Validation\Rule;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_exams', 'slug'),
            ],
            'assembly_mode' => ['required', Rule::in(['manual', 'by_count', 'by_score'])],
            'question_count' => 'required_if:assembly_mode,by_count|nullable|integer|min:1',
            'target_total_score' => 'required_if:assembly_mode,by_score|nullable|integer|min:1',
            'is_published' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_explanations' => 'boolean',
            'time_limit_seconds' => 'nullable|integer|min:0',
            'category_id' => 'nullable|integer',
            'topics' => 'array',
            'topics.*' => 'integer',
            'manual_questions' => 'array',
            'manual_questions.*' => 'integer',
            'pass_type' => 'nullable|in:score,percent',
            'pass_value' => 'nullable|integer|min:0',
            'max_attempts' => 'nullable|integer|min:1',
            'difficulty_split' => 'array',
            'difficulty_split.*' => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $mode = $this->input('assembly_mode');
            $splits = array_map('intval', (array) $this->input('difficulty_split', []));
            if ($mode === 'by_count') {
                $count = (int) $this->input('question_count');
                if ($count > 0 && array_sum($splits) !== $count) {
                    $validator->errors()->add('difficulty_split', 'Difficulty counts must sum to the question count.');
                }
            }
            if ($mode === 'by_score') {
                $target = (int) $this->input('target_total_score');
                if ($target > 0 && array_sum($splits) !== $target) {
                    $validator->errors()->add('difficulty_split', 'Difficulty score targets must sum to the exam target score.');
                }
            }
        });
    }
}
