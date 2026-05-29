<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsApiRelations;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    use FormatsApiRelations;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'topic' => $this->topic,
            'region' => $this->region,
            'content' => $this->content,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'archived_at' => $this->archived_at,
            'audience' => $this->audience,
            'auto_share_social' => $this->auto_share_social,
            'social_channels' => $this->social_channels,
            'author_id' => $this->author_id,
            'party_branch_id' => $this->party_branch_id,
            'image_path' => $this->image_path,
            'attachment_path' => $this->attachment_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'author' => $this->whenLoaded('author', fn () => $this->userSummary($this->author)),
            'party_branch' => $this->whenLoaded('partyBranch', fn () => $this->branchSummary($this->partyBranch)),
        ];
    }
}
