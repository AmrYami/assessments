<?php

namespace Yami\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamPreviewResource extends JsonResource
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
            'seed' => (int) ($this['seed'] ?? 0),
            'question_ids' => array_map('intval', $this['question_ids'] ?? []),
            'mode' => $this['mode'] ?? null,
            'count' => (int) ($this['count'] ?? 0),
            'total_score_preview' => $this['total_score_preview'] ?? null,
        ];
    }
}
