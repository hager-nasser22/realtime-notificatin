<?php

namespace App\Http\Requests\Meetup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetupRequest extends FormRequest
{
    public function meetup()
    {
        return $this->route('meetup');
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->meetup());
    }

    public function rules(): array
    {
        $capacity = max(1, $this->meetup()->confirmedBookings()->count());

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date', 'after:now'],
            'capacity' => ['sometimes', 'integer', "min:{$capacity}", 'max:10000'],
            'requires_approval' => ['sometimes', 'boolean'],
        ];
    }
}
