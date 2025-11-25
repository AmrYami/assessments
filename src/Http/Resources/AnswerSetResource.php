<?php

namespace Amryami\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerSetResource extends JsonResource
{
    /**
     * @param  \Amryami\Assessments\Domain\Models\AnswerSet  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'items' => AnswerSetItemResource::collection($this->whenLoaded('items')),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
