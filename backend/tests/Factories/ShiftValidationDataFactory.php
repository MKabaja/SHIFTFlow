<?php

namespace Tests\Factories;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;

class ShiftValidationDataFactory
{
    private array $attributes = [];

    public static function new(): self
    {
        return new self;
    }

    public function withUser(User $user): self
    {
        $this->attributes['userId'] = $user->id;
        $this->attributes['allowedPositionIds'] = $user->positions->pluck('id')->toArray();
        $this->attributes['maxMinutesPerMonth'] = $user->max_minutes_per_month;
        $this->attributes['minBreakMinutes'] = $user->min_break_minutes;
        $this->attributes['maxMinutesPerQuarter'] = $user->max_minutes_per_quarter;

        if ($user->positions->isNotEmpty()) {
            $this->attributes['positionId'] = $user->positions->first()->id;
        }

        return $this;
    }

    public function onDate(string $date): self
    {
        $this->attributes['date'] = $date;

        return $this;
    }

    public function withPosition(Position $position): self
    {
        $this->attributes['positionId'] = $position->id;

        return $this;
    }

    public function withShiftTime(string $start, string $end): self
    {
        $this->attributes['shiftStart'] = $start;
        $this->attributes['shiftEnd'] = $end;

        return $this;
    }

    public function nightShift(): self
    {
        $this->attributes['shiftStart'] = '22:00';
        $this->attributes['shiftEnd'] = '06:00';

        return $this;
    }

    public function withAccumulatedMinutes(int $minutes): self
    {
        $this->attributes['accumulatedBatchMinutes'] = $minutes;

        return $this;
    }

    public function forUpdate(int $shiftId): self
    {
        $this->attributes['ignoreShiftId'] = $shiftId;

        return $this;
    }

    public function withNoLimits(): self
    {
        $this->attributes['maxMinutesPerMonth'] = null;
        $this->attributes['maxMinutesPerQuarter'] = null;
        $this->attributes['minBreakMinutes'] = null;

        return $this;
    }

    public function create(): ShiftValidationData
    {
        $defaults = [
            'userId' => 1,
            'date' => Carbon::today()->format('Y-m-d'),
            'shiftStart' => '08:00',
            'shiftEnd' => '16:00',
            'positionId' => 1,
            'allowedPositionIds' => [1, 2, 3],
            'maxMinutesPerMonth' => 9600,
            'minBreakMinutes' => 660,
            'maxMinutesPerQuarter' => 28800,
            'ignoreShiftId' => null,
            'accumulatedBatchMinutes' => 0,
        ];

        $data = array_merge($defaults, $this->attributes);

        return new ShiftValidationData(
            userId: $data['userId'],
            date: $data['date'],
            shiftStart: $data['shiftStart'],
            shiftEnd: $data['shiftEnd'],
            positionId: $data['positionId'],
            allowedPositionIds: $data['allowedPositionIds'],
            maxMinutesPerMonth: $data['maxMinutesPerMonth'],
            minBreakMinutes: $data['minBreakMinutes'],
            maxMinutesPerQuarter: $data['maxMinutesPerQuarter'],
            ignoreShiftId: $data['ignoreShiftId'],
            accumulatedBatchMinutes: $data['accumulatedBatchMinutes'],
        );
    }
}
