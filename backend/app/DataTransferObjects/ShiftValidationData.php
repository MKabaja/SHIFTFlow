<?php

namespace App\DataTransferObjects;

readonly class ShiftValidationData
{
    public function __construct(
        public int $userId,
        public string $date,
        public string $shiftStart,
        public int $positionId,
        public string $shiftEnd,
        public array $allowedPositionIds,
        public ?int $maxHoursPerMonth,
        public ?int $minBreakHours,
        public ?int $maxHoursPerQuarter,
        public ?int $ignoreShiftId = null,
        public int $accumulatedBatchMinutes = 0

    ) {}
}
