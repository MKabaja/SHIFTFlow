<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetSchedulesRequest extends FormRequest
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
        $minYear = now()->year;
        $maxYear = $minYear + 5;
        
        return [

        'month' => 
        [
            'nullable',
            'integer',
            'between:1,12'
        ],
        'year'  => 
        [
            'nullable', 
            'integer', 
            "between:{$minYear},{$maxYear}"
        ],
         
        ];
    }
}
