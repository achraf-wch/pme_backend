<?php

namespace App\Http\Requests;

class UpdatePollRequest extends StorePollRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'target_audience' => 'sometimes|required|array|min:1',
            'target_audience.*' => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'is_secret' => 'nullable|boolean',
            'options' => 'sometimes|required|array|min:2',
            'options.*' => 'required|string',
        ];
    }
}
