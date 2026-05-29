<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    use FormatsApiRelations;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'max_attendees' => $this->max_attendees,
            'created_by' => $this->created_by,
            'party_branch_id' => $this->party_branch_id,
            'attachment_path' => $this->attachment_path,
            'audience' => $this->audience,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'registrations_count' => $this->when(isset($this->registrations_count), $this->registrations_count),
            'has_registered' => $this->when(isset($this->has_registered), $this->has_registered),
            'can_register' => $this->when(isset($this->can_register), $this->can_register),
            'recaps_count' => $this->when(isset($this->recaps_count), $this->recaps_count),
            'creator' => $this->whenLoaded('creator', fn () => $this->userSummary($this->creator)),
            'party_branch' => $this->whenLoaded('partyBranch', fn () => $this->branchSummary($this->partyBranch)),
            'recaps' => EventRecapResource::collection($this->whenLoaded('recaps')),
        ];
    }
}
