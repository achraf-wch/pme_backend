<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|in:news,communique,article',
            'topic' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_published' => 'nullable',
            'published_at' => 'nullable|date',
            'audience' => 'required|array|min:1',
            'audience.*' => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'auto_share_social' => 'nullable',
            'social_channels' => 'nullable|array',
            'social_channels.*' => 'string|in:facebook,x,instagram,linkedin,whatsapp',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
        ];
    }
}
