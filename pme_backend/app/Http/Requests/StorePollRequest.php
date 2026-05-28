<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'target_audience' => 'required|array|min:1',
            'target_audience.*' => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'is_secret' => 'nullable|boolean',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string',
        ];
    }
}
