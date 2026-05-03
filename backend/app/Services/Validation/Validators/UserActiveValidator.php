<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;

class UserActiveValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        if ($this->hasConflict($shift)) {
            $this->throwConflictException('User is not active');
        }

    }

    private function hasConflict(ShiftValidationData $shift): bool
    {
        return ! $shift->isUserActive;
    }
}
