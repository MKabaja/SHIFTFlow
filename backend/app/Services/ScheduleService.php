<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Schedule;
use App\Models\Shift;
use App\Services\Batch\BatchPreprocessor;
use App\Services\Batch\BatchValidationService;
use App\Services\Validation\Helpers\TimeHelper;
use App\ValueObjects\BatchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    public function __construct(
        private readonly BatchPreprocessor $batchPreprocessor,
        private readonly BatchValidationService $batchValidationService
    ) {}

    /** @param array<string, mixed> $scheduleData */
    public function create(array $scheduleData): Schedule
    {
        return Schedule::create([
            'name' => $scheduleData['name'],
            'month' => $scheduleData['month'],
            'year' => $scheduleData['year'],
            'description' => $scheduleData['description'] ?? null,
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);
    }

    /** @param array<int, array<string, mixed>> $shiftsData */
    public function addShiftsBatch(Schedule $schedule, array $shiftsData): BatchResult
    {
        $groupedShifts = $this->batchPreprocessor
            ->prepare($shiftsData, $schedule);

        $errors = $this->batchValidationService
            ->validate($groupedShifts);

        if (! empty($errors)) {
            return BatchResult::withErrors($errors);
        }
        $createdShifts = $this->executeShiftsCreation($groupedShifts, $schedule);

        return BatchResult::success($createdShifts);
    }

    /**
     * @param Collection<int|string, Collection<int, array{client_temp_id: string, user_id: int, position_id: int, date: string, shift_start: string, shift_end: string}>> $groupedShifts
     * @return Collection<int, Shift>
     */
    private function executeShiftsCreation(
        Collection $groupedShifts,
        Schedule $schedule
    ): Collection {

        return DB::transaction(function () use ($groupedShifts, $schedule) {
            return $groupedShifts->flatten(1)->map(function ($shiftData) use ($schedule) {
                $minutesWorked = TimeHelper::calculateMinutesDifference(
                    "{$shiftData['date']} {$shiftData['shift_start']}",
                    "{$shiftData['date']} {$shiftData['shift_end']}"
                );

                return Shift::create([
                    'schedule_id' => $schedule->id,
                    'user_id' => $shiftData['user_id'],
                    'position_id' => $shiftData['position_id'],
                    'date' => $shiftData['date'],
                    'shift_start' => $shiftData['shift_start'],
                    'shift_end' => $shiftData['shift_end'],
                    'minutes_worked' => $minutesWorked,
                    'status' => $shiftData['status'] ?? 'scheduled',
                    'notes' => $shiftData['notes'] ?? null,
                ]);
            });
        });
    }
}
