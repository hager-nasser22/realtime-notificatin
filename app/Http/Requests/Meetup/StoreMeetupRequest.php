<?php

namespace App\Http\Requests\Meetup;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after:now'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'requires_approval' => ['sometimes', 'boolean'],
        ];
    }
}
