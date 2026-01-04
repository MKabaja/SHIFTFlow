<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Get hours report for specific user
     */
    public function generateHourlyReport(int $userId, int $month, int $year): array
    {
        $schedules = $this->getSchedulesForSpecificUser($userId, $month, $year);

        $totalHours = $schedules->sum('hours_worked');

        $hoursByPosition = $this->sumWorkedHoursGroupedBy('position.name', $schedules);

        $hoursByDate = $this->sumWorkedHoursGroupedBy('date', $schedules);

        return [
            'total_hours' => $totalHours,
            'by_position' => $hoursByPosition,
            'by_date' => $hoursByDate,
        ];
    }

    /**
     * Get payroll report for all users
     */
    public function generatePayrollReport(int $month, int $year): array
    {
        $schedules = $this->getSchedulesForMonth($month, $year);

        $schedulesWithCost = $this->calculateCosts($schedules);

        $employees = $this->aggregateEmployeeWorkAndPay($schedulesWithCost);

        $byPosition = $schedulesWithCost->groupBy('position.name')->map->sum('cost');

        $totalCost = $schedulesWithCost->sum('cost');

        return [
            'employees' => $employees,
            'by_position' => $byPosition,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Get coverage report for specific date
     */
    public function generateCoverageReport(string $date): array
    {
        $schedules = $this->getSchedulesForDate($date);

        $coverage = $schedules->groupBy('position.name')->map->count();

        return [
            'total_employees' => $schedules->count(),
            'coverage' => $coverage,
        ];

    }

    /**
     * Get schedules for specific user and month
     */
    private function getSchedulesForSpecificUser(int $userId, int $month, int $year): Collection
    {
        return Schedule::with('position')
            ->active()
            ->where('user_id', $userId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

    }

    private function sumWorkedHoursGroupedBy(string $groupName, Collection $collection): Collection
    {
        return $collection->groupBy($groupName)->map->sum('hours_worked');

    }

    /**
     * Get schedules for all users in specific month
     */
    private function getSchedulesForMonth(int $month, int $year): Collection
    {
        return Schedule::with('user', 'position')
            ->active()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

    }

    /**
     * Calculate cost for each schedule
     */
    private function calculateCosts(Collection $collection): Collection
    {
        return $collection->map(function ($schedule) {
            $schedule->cost = $schedule->hours_worked *
                ($schedule->hourly_rate ?? $schedule->user->hourly_rate);

            return $schedule;
        });
    }

    private function aggregateEmployeeWorkAndPay(Collection $collection): Collection
    {
        return $collection->groupBy('user_id')->map(function ($group) {
            $user = $group->first()->user;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'hours' => $group->sum('hours_worked'),
                'rate' => $user->hourly_rate,
                'cost' => $group->sum('cost'),
            ];
        })->values();
    }

    private function getSchedulesForDate(string $date): Collection
    {
        return Schedule::with('position')
            ->active()
            ->whereDate('date', $date)
            ->get();
    }
}
