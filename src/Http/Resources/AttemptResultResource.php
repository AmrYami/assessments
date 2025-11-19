<?php

namespace Fakeeh\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResultResource extends JsonResource
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
            'score' => (int) ($this['score'] ?? 0),
            'percent' => (int) ($this['percent'] ?? 0),
            'passed' => (bool) ($this['passed'] ?? false),
            'total_possible' => $this['total_possible'] ?? null,
            'details' => $this['details'] ?? null,
            'review' => $this['review'] ?? null,
        ];
    }
}
