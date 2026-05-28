<?php

namespace App\Http\Requests;

class UpdateEventRequest extends StoreEventRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'audience' => 'sometimes|required|array|min:1',
            'audience.*' => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ];
    }
}
