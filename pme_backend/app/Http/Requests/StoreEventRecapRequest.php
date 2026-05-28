<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRecapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'photos' => 'nullable|array|max:12',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ];
    }
}
