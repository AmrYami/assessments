<?php

namespace Fakeeh\Assessments\Http\Requests\Admin;

use Fakeeh\Assessments\Domain\Models\AnswerSet;
use Illuminate\Validation\Rule;

class UpdateAnswerSetRequest extends StoreAnswerSetRequest
{
    public function rules(): array
    {
        /** @var AnswerSet|null $answerSet */
        $answerSet = $this->route('answerSet');
        $answerSetId = $answerSet instanceof AnswerSet ? $answerSet->getKey() : null;

        return array_replace(parent::rules(), [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_answer_sets', 'slug')->ignore($answerSetId),
            ],
        ]);
    }
}
