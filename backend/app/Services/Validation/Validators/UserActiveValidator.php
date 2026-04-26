<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\User;

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
        return User::where('id', $shift->userId)
            ->where('is_active', false)
            ->exists();
    }
}
