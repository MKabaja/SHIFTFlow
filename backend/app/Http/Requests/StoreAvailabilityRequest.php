<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'exists:users,id'],

            'notes' => ['nullable', 'string', 'max:255'],

            'is_available' => ['required', 'boolean'],

            'date' => ['required', 'date'],

        ];
    }
}
