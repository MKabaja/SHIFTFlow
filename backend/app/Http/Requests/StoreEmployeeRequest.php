<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],

            'pin' => ['required', 'digits:4', 'numeric'],

            'positions' => ['required', 'array', 'min:1'],

            'positions.*' => ['required', 'integer', 'exists:positions,id'],

            'hourly_rate' => ['nullable', 'numeric', 'min:0']


        ];
    }
}
