<?php

namespace App\Services\Validation\Validators;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Availability;
use Illuminate\Validation\ValidationException;

class AvailabilityValidator
{
    public function validate(ShiftValidationData $shift): void
    {
        $availability = Availability::where('user_id', $shift->userId)
            ->where('date', $shift->date)
            ->first();

        if ($availability && ! $availability->is_available) {
            throw ValidationException::withMessages([
                'date' => ["User is unavailable on {$shift->date}"],
            ]);
        }

    }
}
