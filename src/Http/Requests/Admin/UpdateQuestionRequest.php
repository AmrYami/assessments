<?php

namespace Streaming\Assessments\Http\Requests\Admin;

use Streaming\Assessments\Domain\Models\Question;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    public function rules(): array
    {
        /** @var Question|null $question */
        $question = $this->route('question');
        $ignoreId = $question instanceof Question ? $question->getKey() : null;

        $rules = parent::rules();

        $rules['slug'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('assessment_questions', 'slug')->ignore($ignoreId),
        ];

        return $rules;
    }
}
