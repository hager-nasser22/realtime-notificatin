<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'description'       => $this->description,
            'location'          => $this->location,
            'starts_at'         => $this->starts_at->toIso8601String(),
            'capacity'          => $this->capacity,
            'requires_approval' => $this->requires_approval,
            'organizer'         => new UserResource($this->whenLoaded('organizer')),
            'left_capacity'     => $this->whenCounted('confirmedBookings', fn($count) => $this->capacity - $count),
        ];
    }
}
