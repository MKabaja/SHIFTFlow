<?php

namespace App\Services\Validation;

use App\DataTransferObjects\ShiftValidationData;

class ValidationService
{
    /** @param ShiftValidatorInterface[] $validators */
    public function __construct(private array $validators) {}

    /**
     * Validate shift data through all validators.
     * Executes validators in optimized order (fast to slow).
     * Stops on first validation failure.
     *
     * @param  ShiftValidationData  $shift  Data Transfer Object
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(ShiftValidationData $shift): void
    {

        foreach ($this->validators as $validator) {
            $validator->validate($shift);
        }

    }
}
