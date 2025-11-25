<?php

namespace Amryami\Assessments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptHeartbeatResource extends JsonResource
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
            'server_now' => $this['server_now'] ?? null,
            'expires_at' => $this['expires_at'] ?? null,
            'status' => $this['status'] ?? null,
        ];
    }
}
