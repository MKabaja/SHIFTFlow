<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['manager', 'admin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('schedules')
                    ->where(fn ($q) => $q
                        ->where('year', $this->year)
                    ),

            ],
            'year' => [
                    'required',
                    'integer',
                    'min:2026',

            ],
            'description' => [
                    'nullable',
                    'string',
            ],
        ];
    }
}
