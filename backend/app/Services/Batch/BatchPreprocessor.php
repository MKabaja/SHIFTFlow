<?php

namespace App\Services\Batch;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;

class BatchPreprocessor
{
    public function prepare(array $shiftsData, Schedule $schedule): Collection
    {
        $validator = $this->validateArrayFormat($shiftsData);

        $validator->after(function ($validator) use ($shiftsData, $schedule) {
            $this->areDatesConsistentWithSchedule($shiftsData, $schedule, $validator);
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return collect($shiftsData)
            ->groupBy('user_id')
            ->map(fn ($userShifts) => $userShifts
                ->sortBy([
                    ['date', 'asc'],
                    ['shift_start', 'asc'],
                ])
                ->values()
            );

    }

    private function validateArrayFormat(array $data): Validator
    {
        $messages = [
            '*.user_id.required' => 'Employee selection is required at row :index.',
            '*.user_id.exists' => 'The selected employee at row :index is invalid.',
            '*.position_id.required' => 'Position is required at row :index.',
            '*.position_id.exists' => 'The selected position at row :index is invalid.',
            '*.date.required' => 'Date is required at row :index.',
            '*.date.date' => 'Invalid date format at row :index.',
            '*.shift_start.required' => 'Start time is required at row :index.',
            '*.shift_end.required' => 'End time is required at row :index.',
        ];

        return ValidatorFacade::make($data, [
            '*.user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            '*.position_id' => [
                'required',
                'integer',
                'exists:positions,id',
            ],

            '*.date' => [
                'required',
                'date_format:Y-m-d',
            ],

            '*.shift_start' => [
                'required',
                'date_format:H:i',
            ],

            '*.shift_end' => [
                'required',
                'date_format:H:i',
            ],
            '*.client_temp_id' => [
                'required',
                'string',
            ],
        ], $messages);
    }

    private function areDatesConsistentWithSchedule(array $shiftsData, Schedule $schedule, Validator $validator)
    {
        foreach ($shiftsData as $index => $shift) {
            try {
                $date = Carbon::parse($shift['date']);
            } catch (\Exception $e) {
                continue;
            }

            if ($date->month !== $schedule->month
                || $date->year !== $schedule->year) {
                $validator->errors()->add(
                    "{$index}.date",
                    "The date {$shift['date']} does not match the schedule period ({$schedule->month}/{$schedule->year})."
                );
            }
        }

    }
}
