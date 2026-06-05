<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ChangePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hasPin = $this->user()->pin_hashed !== null;

        return [
            'current_pin' => $hasPin
                ? ['required', 'digits:4', $this->currentPinRule()]
                : ['nullable', 'digits:4'],
            'new_pin' => ['required', 'digits:4', 'confirmed'],
            'new_pin_confirmation' => ['required', 'digits:4'],
        ];
    }

    private function currentPinRule(): Closure
    {
        $user = $this->user();

        return function (string $attribute, mixed $value, Closure $fail) use ($user): void {
            if (! Hash::check($value, $user->pin_hashed)) {
                $fail('The current PIN is incorrect.');
            }
        };
    }
}
