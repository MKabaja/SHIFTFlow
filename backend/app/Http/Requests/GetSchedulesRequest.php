<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $minYear = now()->year;
        $maxYear = $minYear + 5;

        return [

            'month' => [
                'nullable',
                'integer',
                'between:1,12',
            ],
            'year' => [
                'nullable',
                'integer',
                "between:{$minYear},{$maxYear}",
            ],
            'per_page' => [
                'integer',
                'nullable',
                'min:1',
                'max:150',
            ],
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

        ];
    }
}
