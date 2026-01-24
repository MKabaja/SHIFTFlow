<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchShiftsRequest extends FormRequest
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
            'shifts' => ['required', 'array', 'min:1'],
            'shifts.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'shifts.*.position_id' => ['required', 'integer', 'exists:positions,id'],
            'shifts.*.date' => ['required', 'date_format:Y-m-d'],
            'shifts.*.shift_start' => ['required', 'date_format:H:i'],
            'shifts.*.shift_end' => ['required', 'date_format:H:i'],
            'shifts.*.client_temp_id' => ['required', 'string', 'distinct'],
            'shifts.*.notes' => ['nullable', 'string', 'max:500'],
            'shifts.*.status' => ['nullable', 'string', 'in:scheduled,cancelled'],
        ];
    }
}
