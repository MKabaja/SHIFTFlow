<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $minYear = now()->year - 1;
        $maxYear = now()->year + 5;

        return [

            'month' => [
                'required',
                'integer',
                'between:1,12',
            ],
            'year' => [
                'required',
                'integer',
                "between:{$minYear},{$maxYear}",
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

        ];
    }
}
