<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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

            'pin' => ['sometimes', 'digits:4', 'numeric'],

            'positions' => ['sometimes', 'array', 'min:1'],

            'positions.*' => ['sometimes', 'integer', 'exists:positions,id'],

            'hourly_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'max_minutes_per_month' => ['nullable', 'integer', 'min:0'],

            'max_minutes_per_quarter' => ['nullable', 'integer', 'min:0'],

            'min_break_minutes' => ['nullable', 'integer', 'min:0'],

            'contract_type' => ['nullable', 'string', 'in:employment_contract,mandate_contract'],
        ];
    }
}
