<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'exists:users,id'],

            'notes' => ['nullable', 'string', 'max:255'],

            'is_available' => ['required', 'boolean'],

            'date' => ['required', 'date',

                Rule::unique('availabilities')->where(function ($query) {
                    return $query->where('user_id', $this->user_id);
                }),
            ],
        ];
    }
}
