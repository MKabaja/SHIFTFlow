<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',

            ],
            'per_page' => [
                'integer',
                'nullable',
                'min:1',
                'max:150',
            ],
        ];
    }
}
