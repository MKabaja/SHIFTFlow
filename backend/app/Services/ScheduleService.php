<?php

namespace App\Services;

use App\Models\Schedule;
use App\Services\Validation\ValidationService;
use App\ValueObjects\BatchResult;
use Illuminate\Support\Facades\Auth;

class ScheduleService
{
    public function __construct(
        private ValidationService $validationService
    ) {}

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

    public function addShiftsBatch(Schedule $schedule, array $shifts): BatchResult
    {
        // ...
        if (! empty($errors)) {
            return BatchResult::withErrors($errors);
        }

        return BatchResult::success($shifts);
    }
}
