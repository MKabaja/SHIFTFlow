<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Get hours report for specific user
     */
    public function getHoursReport(int $userId, int $month, int $year): array
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
    public function getPayrollReport(int $month, int $year): array
    {
        // TODO: Twój kod
    }

    /**
     * Get coverage report for specific date
     */
    public function getCoverageReport(string $date): array
    {
        // TODO: Twój kod (zadanie 25.3)
    }

    private function getYearAndMonth(Request $request): array
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return [$month, $year];
    }

    /**
     * Get schedules for specific user and month
     */
    private function getSchedulesForSpecificUser(int $userId, int $month, int $year): Collection
    {
        return Schedule::with('position')
            ->where('user_id', $userId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();
    }

    private function sumWorkedHoursGroupedBy(string $groupName, Collection $collection): Collection
    {
        return $collection->groupBy($groupName)->map->sum('hours_worked');

    }

    // /**
    //  * Get schedules for all users in specific month
    //  */
    // private function getSchedulesForMonth(int $month, int $year): Collection
    // {
    //     // TODO: Twój kod
    // }

    // /**
    //  * Calculate cost for each schedule
    //  */
    // private function calculateCosts(Collection $schedules): Collection
    // {
    //     // TODO: Twój kod
    // }

    // /**
    //  * Group schedules by position name and sum hours
    //  */
    // private function groupByPosition(Collection $schedules): Collection
    // {
    //     // TODO: Twój kod
    // }

    // /**
    //  * Group schedules by date and sum hours
    //  */
    // private function groupByDate(Collection $schedules): Collection
    // {
    //     // TODO: Twój kod
    // }

    // /**
    //  * Calculate costs grouped by employee
    //  */
    // private function calculateEmployeeCosts(Collection $schedules): Collection
    // {
    //     // TODO: Twój kod
    // }

    // /**
    //  * Calculate costs grouped by position
    //  */
    // private function calculatePositionCosts(Collection $schedules): Collection
    // {
    //     // TODO: Twój kod
    // }
}
