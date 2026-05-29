<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventRecapResource extends JsonResource
{
    use FormatsApiRelations;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'created_by' => $this->created_by,
            'title' => $this->title,
            'content' => $this->content,
            'photos' => $this->photos,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => $this->whenLoaded('creator', fn () => $this->userSummary($this->creator)),
        ];
    }
}
