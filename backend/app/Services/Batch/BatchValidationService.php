<?php

declare(strict_types=1);

namespace App\Services\Batch;

use App\DataTransferObjects\ShiftValidationData;
use App\Models\User;
use App\Services\Validation\Helpers\TimeHelper;
use App\Services\Validation\ValidationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BatchValidationService
{
    public function __construct(
        private readonly ValidationService $validationService
    ) {}

    /**
     * Validate a batch of shifts grouped by user.
     *
     * @param Collection<int|string, Collection<int, array{
     *     client_temp_id: string,
     *     user_id: int,
     *     position_id: int,
     *     date: string,
     *     shift_start: string,
     *     shift_end: string
     * }>> $groupedShifts
     * @return array<string, array<string, string[]>> Key is client_temp_id, value is validation errors
     */
    public function validate(Collection $groupedShifts): array
    {
        $errors = [];

        $userIds = $groupedShifts->keys();
        $users = User::whereIn('id', $userIds)
            ->with('positions')
            ->get()
            ->keyBy('id');

        foreach ($groupedShifts as $userId => $userShifts) {

            $user = $users->get($userId);

            if (! $user) {
                $this->addUserError($userShifts, $errors);

                continue;
            }

            // Running total of minutes in this batch — passed to ValidationService so
            // month/quarter hour-limit validators see batch shifts before they hit the DB.
            $accumulatedMinutes = 0;
            $previousShiftsInBatch = [];

            foreach ($userShifts as $shiftData) {

                $clientTempId = $shiftData['client_temp_id'];
                try {
                    $this->validateInternalConflicts(
                        $shiftData,
                        $previousShiftsInBatch,
                        $user->min_break_minutes
                    );
                    $dto = $this->createDTO(
                        $shiftData,
                        $user,
                        $accumulatedMinutes
                    );
                    $this->validationService->validate($dto);

                    $shiftMinutes = $this->calculateShiftMinutes($shiftData);
                    $accumulatedMinutes += $shiftMinutes;
                    $previousShiftsInBatch[] = $shiftData;

                } catch (ValidationException $e) {
                    $errors[$clientTempId] = $e->errors();
                }

            }
        }

        return $errors;
    }

    /**
     * @param  array<string,array<string, array<string>>>  $errors
     * @param  Collection<int,
     * array{
     *      client_temp_id: string,
     *      user_id: int,
     *      position_id: int,
     *      date: string,
     *      shift_start: string,
     *      shift_end: string,
     *      }> $userShifts
     */
    private function addUserError(Collection $userShifts, array &$errors): void
    {
        foreach ($userShifts as $shiftData) {
            $errors[$shiftData['client_temp_id']] = [
                'user_id' => ['User not found'],
            ];
        }

    }

    /**
     * @param array{
     *      user_id:int,
     *      date:string,
     *      shift_start: string,
     *      shift_end: string,
     *      position_id: int,
     *    } $shiftData
     */
    private function createDTO(array $shiftData, User $user, int $accumulatedMinutes): ShiftValidationData
    {
        return new ShiftValidationData(
            userId: $shiftData['user_id'],
            date: $shiftData['date'],
            shiftStart: $shiftData['shift_start'],
            shiftEnd: $shiftData['shift_end'],
            isUserActive: $user->is_active,
            positionId: $shiftData['position_id'],
            allowedPositionIds: $user->positions->pluck('id')->toArray(),
            maxMinutesPerMonth: $user->max_minutes_per_month,
            minBreakMinutes: $user->min_break_minutes,
            maxMinutesPerQuarter: $user->max_minutes_per_quarter,
            ignoreShiftId: null,
            accumulatedBatchMinutes: $accumulatedMinutes,
        );
    }

    /**
     * @param array{
     *      date:string,
     *      shift_start: string,
     *      shift_end: string
     *    } $currentShift
     * @param array<int, array{
     *      date: string,
     *      shift_start: string,
     *      shift_end: string
     *      }> $previousShiftsInBatch
     */
    private function validateInternalConflicts(
        array $currentShift,
        array $previousShiftsInBatch,
        ?int $minBreakMinutes
    ): void {
        foreach ($previousShiftsInBatch as $previousShift) {
            if ($this->shiftsOverlap($currentShift, $previousShift)) {
                throw ValidationException::withMessages([
                    'conflict' => ['This shift overlaps with another shift in the batch.'],
                ]);
            }

            if ($this->hasInsufficientBreak($currentShift, $previousShift, $minBreakMinutes)) {
                throw ValidationException::withMessages([
                    'break' => ['Insufficient break time between shifts in the batch.'],
                ]);
            }
        }
    }

    /**
     * @param  array{
     *      date: string,
     *      shift_start: string,
     *      shift_end: string
     *      } $shiftA
     * @param  array{
     *      date: string,
     *      shift_start: string,
     *      shift_end: string
     *      } $shiftB
     */
    private function shiftsOverlap(array $shiftA, array $shiftB): bool
    {

        $startA = TimeHelper::createFullDateTime(
            $shiftA['date'],
            $shiftA['shift_start']
        );
        $endA = TimeHelper::createFullDateTime(
            $shiftA['date'],
            $shiftA['shift_end']
        );

        $startB = TimeHelper::createFullDateTime(
            $shiftB['date'],
            $shiftB['shift_start']
        );
        $endB = TimeHelper::createFullDateTime(
            $shiftB['date'],
            $shiftB['shift_end']
        );

        // end < start means the shift crosses midnight — advance end to next day
        if ($endA->lessThan($startA)) {
            $endA->addDay();
        }

        if ($endB->lessThan($startB)) {
            $endB->addDay();
        }

        return $startA->lessThan($endB) && $endA->greaterThan($startB);
    }

    /**
     * @param array{
     *      date: string,
     *      shift_start: string,
     *      shift_end: string
     *      } $currentShift
     * @param array{
     *      date: string,
     *      shift_start: string,
     *      shift_end: string
     *      } $previousShift
     */
    private function hasInsufficientBreak(
        array $currentShift,
        array $previousShift,
        ?int $minBreakMinutes
    ): bool {
        if ($minBreakMinutes === null) {
            return false;
        }
        $previousShiftEnd = TimeHelper::createFullDateTime(
            $previousShift['date'],
            $previousShift['shift_end']
        );

        $currentShiftStart = TimeHelper::createFullDateTime(
            $currentShift['date'],
            $currentShift['shift_start']
        );
        if ($previousShift['shift_end'] < ($previousShift['shift_start'])) {
            $previousShiftEnd->addDay();
        }
        $breakMinutes = $previousShiftEnd->diffInMinutes($currentShiftStart, false);

        if ($breakMinutes < $minBreakMinutes) {
            return true;
        }

        return false;
    }

    /**
     * @param array{
     *      user_id:int,
     *      date:string,
     *      shift_start: string,
     *      shift_end: string,
     *      position_id: int,
     *    } $shiftData
     */
    private function calculateShiftMinutes(array $shiftData): int
    {
        $date = $shiftData['date'];

        return TimeHelper::calculateMinutesDifference(
            "{$date} {$shiftData['shift_start']}",
            "{$date} {$shiftData['shift_end']}"
        );

    }
}
