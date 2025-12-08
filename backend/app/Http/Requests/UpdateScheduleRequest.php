<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // check if user is logged in
        $user = $this->user();

        if (!$user) {
            return false;
        }
        //check if user role is allowed
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
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],

            'position_id' => ['sometimes', 'required', 'integer', 'exists:positions,id'],

            'date' => ['sometimes', 'required', 'date_format:Y-m-d'],

            'shift_start' => ['sometimes', 'required', 'date_format:H:i'],

            'shift_end' => ['sometimes', 'required', 'date_format:H:i', 'after:shift_start'],
        ];
    }
}
