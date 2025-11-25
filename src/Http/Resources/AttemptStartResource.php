<?php

namespace Amryami\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptStartResource extends JsonResource
{
    /**
     * @param  array  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $questions = $this->resource['questions'] ?? [];

        return [
            'attempt_id' => (int) ($this->resource['attempt_id'] ?? 0),
            'expires_at' => $this->resource['expires_at'] ?? null,
            'questions' => AttemptQuestionResource::collection(collect($questions)),
        ];
    }
}
