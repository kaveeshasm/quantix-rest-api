<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category->value,
            'status' => $this->status->value,
            'requester' => new UserResource($this->whenLoaded('requester')),
            'assignee' => $this->whenLoaded(
                'assignee',
                fn () => $this->assignee ? new UserResource($this->assignee) : null
            ),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'activity' => ActivityLogResource::collection($this->whenLoaded('activityLogs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
