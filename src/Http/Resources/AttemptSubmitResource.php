<?php

namespace Fakeeh\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptSubmitResource extends JsonResource
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
        return [
            'attempt_id' => (int) ($this['attempt_id'] ?? 0),
            'score' => (int) ($this['score'] ?? 0),
            'score_auto' => (int) ($this['score_auto'] ?? 0),
            'score_manual' => (int) ($this['score_manual'] ?? 0),
            'percent' => (int) ($this['percent'] ?? 0),
            'review_status' => $this['review_status'] ?? null,
            'passed' => (bool) ($this['passed'] ?? false),
            'details' => $this['details'] ?? null,
            'total_possible' => $this['total_possible'] ?? null,
        ];
    }
}
