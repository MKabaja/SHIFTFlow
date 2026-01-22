<?php

namespace App\Services;

use App\Models\Schedule;
use App\Services\Validation\ValidationService;

class ScheduleService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private ValidationService $validationService
    ) {}

    public function create(array $data): Schedule
    {
        return Schedule::create([
            ...$data,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
    }
}
