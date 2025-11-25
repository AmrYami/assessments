<?php

namespace Yami\Assessments\Http\Requests\Admin;

use Yami\Assessments\Domain\Models\Exam;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends StoreExamRequest
{
    public function rules(): array
    {
        $exam = $this->route('exam');
        $examId = $exam instanceof Exam ? $exam->getKey() : null;

        return array_replace(parent::rules(), [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_exams', 'slug')->ignore($examId),
            ],
        ]);
    }
}
