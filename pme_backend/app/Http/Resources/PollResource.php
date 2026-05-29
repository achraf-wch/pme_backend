<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    use FormatsApiRelations;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_secret' => $this->is_secret,
            'created_by' => $this->created_by,
            'party_branch_id' => $this->party_branch_id,
            'target_audience' => $this->target_audience,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'can_vote' => $this->when(isset($this->can_vote), $this->can_vote),
            'has_voted' => $this->when(isset($this->has_voted), $this->has_voted),
            'options' => PollOptionResource::collection($this->whenLoaded('options')),
            'party_branch' => $this->whenLoaded('partyBranch', fn () => $this->branchSummary($this->partyBranch)),
        ];
    }
}
