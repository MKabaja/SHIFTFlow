<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use Illuminate\Validation\ValidationException;

class PositionPermissionValidator
{
    public function validate(ShiftValidationData $shift): void
    {

        if (! in_array($shift->positionId, $shift->allowedPositionIds)) {

            throw ValidationException::withMessages([
                'position_id' => ["User does not have permission for this position (ID: $shift->positionId)"],
            ]);
        }

    }
}
