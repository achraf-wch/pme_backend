<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventRegistrationResource extends JsonResource
{
    use FormatsApiRelations;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user', fn () => array_merge(
                $this->userSummary($this->user),
                ['party_branch' => $this->branchSummary($this->user->partyBranch)]
            )),
            'event' => $this->whenLoaded('event', fn () => new EventResource($this->event)),
        ];
    }
}
