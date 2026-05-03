<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return in_array($user->role, ['manager', 'admin'], true);
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'position_id' => [
                'required',
                'integer',
                'exists:positions,id',
            ],

            'date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'shift_start' => [
                'required',
                'date_format:H:i',
            ],

            'shift_end' => [
                'required',
                'date_format:H:i',
            ],

            'schedule_id' => [
                'nullable',
                'integer',
                'exists:schedules,id',
            ],
            'status' => [
                'nullable',
                'string',
                'in:scheduled,cancelled',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
