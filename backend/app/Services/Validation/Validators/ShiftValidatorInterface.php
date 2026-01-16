<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;

interface ShiftValidatorInterface
{
    /**
     * Validate shift data against specific business rule.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(ShiftValidationData $shift): void;
}
