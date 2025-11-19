<?php

namespace Fakeeh\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptQuestionResource extends JsonResource
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
            'id' => (int) ($this['id'] ?? 0),
            'text' => $this['text'] ?? '',
            'response_type' => $this['response_type'] ?? null,
            'selection_mode' => $this['selection_mode'] ?? null,
            'weight' => (int) ($this['weight'] ?? 0),
            'note_enabled' => (bool) ($this['note_enabled'] ?? false),
            'note_required' => (bool) ($this['note_required'] ?? false),
            'note_hint' => $this['note_hint'] ?? null,
            'max_choices' => $this['max_choices'] ?? null,
            'options' => $this['options'] ?? null,
            'response_parts' => $this['response_parts'] ?? null,
        ];
    }
}
