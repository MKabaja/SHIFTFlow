<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
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
        $positionId = $this->route('position');

        return [
            'name' => [
                'required',
                'string',
                Rule::unique('positions', 'name')->ignore($positionId),
                'max:4',
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => [
                'nullable',
                'string',
                'regex:/^#[A-Fa-f0-9]{6}$/',
            ],
        ];
    }
}
