<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class StoreAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var string|null $role */
        $role = $this->user()?->role;

        /** @var string $earliestDateForEmployee */
        $earliestDateForEmployee = now()->startOfMonth()->subMonth()->toDateString();

        /** @var RequiredIf $userIdIsRequired */
        $userIdIsRequired = Rule::requiredIf($role === 'admin');

        /** @var ConditionalRules $employeeDateLimit */
        $employeeDateLimit = Rule::when($role === 'employee', ["after_or_equal:{$earliestDateForEmployee}"]);

        return [
            'user_id' => [
                $userIdIsRequired,
                'integer',
                'exists:users,id',
            ],

            'notes' => ['nullable', 'string', 'max:255'],

            'is_available' => ['required', 'boolean'],

            'date' => [
                'required',
                'date_format:Y-m-d',
                $employeeDateLimit,
            ],

        ];
    }
}
