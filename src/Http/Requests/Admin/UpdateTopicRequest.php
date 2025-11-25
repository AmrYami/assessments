<?php

namespace Amryami\Assessments\Http\Requests\Admin;

use Amryami\Assessments\Domain\Models\Topic;
use Illuminate\Validation\Rule;

class UpdateTopicRequest extends StoreTopicRequest
{
    public function rules(): array
    {
        /** @var Topic|null $topic */
        $topic = $this->route('topic');
        $topicId = $topic instanceof Topic ? $topic->getKey() : null;

        return array_replace(parent::rules(), [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assessment_topics', 'slug')->ignore($topicId),
            ],
        ]);
    }
}
