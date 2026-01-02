<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function getHoursReport(Request $request, $userId): JsonResponse
    {

        [$month,$year] = $this->getYearAndMonth($request);

        $user = User::findOrFail($userId);
        $this->authorize('viewReport', $user);
        $report = $this->reportService->getHoursReport($userId, $month, $year);

        $schedules = Schedule::with('position')
            ->where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $totalHours = $schedules->sum('hours_worked');

        $hoursByPosition = $schedules->groupBy('position.name')->map->sum('hours_worked');

        $hoursByDate = $schedules->groupBy('date')->map->sum('hours_worked');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'month' => (int) $month,
            'year' => (int) $year,
            'total_hours' => $totalHours,
            'by_position' => $hoursByPosition,
            'by_date' => $hoursByDate,
        ]);
    }

    public function getEmployeeSalary(Request $request): JsonResponse
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        $schedules = Schedule::with('user', 'position')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $schedules = $schedules->map(function ($schedule) {
            $schedule->cost = $schedule->hours_worked *
                ($schedule->hourly_rate ?? $schedule->user->hourly_rate);

            return $schedule;
        });

        $employees = $schedules->groupBy('user_id')->map(function ($group) {
            $user = $group->first()->user;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'hours' => $group->sum('hours_worked'),
                'rate' => $user->hourly_rate,
                'cost' => $group->sum('cost'),
            ];
        })->values();

        $byPosition = $schedules->groupBy('position.name')->map->sum('cost');

        $totalCost = $schedules->sum('cost');

        return response()->json([
            'month' => (int) $month,
            'year' => (int) $year,
            'employees' => $employees,
            'by_position' => $byPosition,
            'total_cost' => $totalCost,
        ]);

    }

    private function getYearAndMonth(Request $request): array
    {
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);

        return [$month, $year];
    }
}
