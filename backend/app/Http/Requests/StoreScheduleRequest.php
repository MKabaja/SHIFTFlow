<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['manager', 'admin'], true);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minYear = now()->year;
        $maxYear = $minYear + 5;

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
                    ->where(
                        fn ($q) => $q
                            ->where('year', $this->year)
                    ),

            ],
            'year' => [
                'required',
                'integer',
                "between:{$minYear},{$maxYear}",

            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'month.unique' => 'The schedule for this month and year has already been created.',
        ];
    }
}
