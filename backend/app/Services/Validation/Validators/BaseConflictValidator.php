<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Services\Validation\ShiftValidatorInterface;
use Illuminate\Validation\ValidationException;

abstract class BaseConflictValidator implements ShiftValidatorInterface
{
    /**
     * Validate shift data against specific business rule.
     * Each child validator must implement its own validation logic.
     *
     * @throws ValidationException
     */
    abstract public function validate(ShiftValidationData $shift): void;

    /**
     * This method ensures a consistent error message.
     *
     * @throws ValidationException
     */
    protected function throwConflictException(
        string $message,
    ): void {

        throw ValidationException::withMessages([
            'conflict' => [$message],
        ]);
    }
}
