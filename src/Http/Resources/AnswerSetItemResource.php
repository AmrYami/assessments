<?php

namespace Yami\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerSetItemResource extends JsonResource
{
    /**
     * @param  \Yami\Assessments\Domain\Models\AnswerSetItem  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'label' => $this->label,
            'value' => $this->value,
            'is_active' => (bool) $this->is_active,
            'position' => (int) $this->position,
        ];
    }
}
