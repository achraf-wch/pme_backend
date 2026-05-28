<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:120',
            'type' => 'nullable|string|max:40',
            'topic' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'archived' => 'nullable|boolean',
        ];
    }
}
