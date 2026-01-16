<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;

class PositionPermissionValidator extends BaseConflictValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        if ($this->hasConflict($shift)) {

            $this->throwConflictException('User has no permission for the selected position');
        }

    }

    private function hasConflict(ShiftValidationData $shift): bool
    {
        return ! in_array(
            $shift->positionId,
            $shift->allowedPositionIds
        );
    }
}
