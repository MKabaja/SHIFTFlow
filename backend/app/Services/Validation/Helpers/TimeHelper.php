<?php

declare(strict_types=1);

namespace App\Services\Validation\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

class TimeHelper
{
    public static function formatDateForQuery(string|DateTimeInterface $input): string
    {
        if ($input instanceof DateTimeInterface) {
            return $input->format('Y-m-d');
        }

        try {
            return Carbon::parse($input)->format('Y-m-d');

        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                "Unable to parse date format: {$input}"
            );
        }

    }

    public static function formatTimeForQuery(string|DateTimeInterface $input): string
    {
        if ($input instanceof DateTimeInterface) {
            return $input->format('H:i');
        }
        try {
            return Carbon::parse($input)
                ->format('H:i');

        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                "Unable to parse time format: {$input}"
            );
        }
    }

    public static function createFullDateTime(string|DateTimeInterface $day, string|DateTimeInterface $time): Carbon
    {
        $formattedDay = self::formatDateForQuery($day);
        $formattedTime = self::formatTimeForQuery($time);

        return Carbon::parse($formattedDay.' '.$formattedTime);
    }

    public static function createMonthRange(string|DateTimeInterface $date): object
    {

        $carbonDate = Carbon::parse(self::formatDateForQuery($date));

        return (object) [
            'start' => $carbonDate->copy()->startOfMonth(),
            'end' => $carbonDate->copy()->endOfMonth(),
        ];
    }

    public static function createQuarterRange(string|DateTimeInterface $date): object
    {
        $carbonDate = Carbon::parse(self::formatDateForQuery($date));

        return (object) [

            'start' => $carbonDate->copy()->startOfQuarter(),
            'end' => $carbonDate->copy()->endOfQuarter(),
        ];
    }

    public static function calculateMinutesDifference(string $startTime, string $endTime): int
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }
}
