<?php

declare(strict_types=1);

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

            'hourly_rate' => ['nullable', 'numeric', 'min:0'],

            'contract_type' => ['nullable', 'string', 'in:employment_contract,mandate_contract'],

            'max_minutes_per_month' => ['nullable', 'integer', 'min:0'],

            'max_minutes_per_quarter' => ['nullable', 'integer', 'min:0'],

            'min_break_minutes' => ['nullable', 'integer', 'min:0'],

        ];
    }
}
