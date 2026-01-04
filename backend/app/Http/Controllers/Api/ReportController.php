<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function employeeHours(Request $request, $userId): JsonResponse
    {
        [$month,$year] = $this->getYearAndMonth($request);

        $user = User::findOrFail($userId);
        $this->authorize('viewReport', $user);

        $report = $this->reportService->generateHourlyReport($userId, $month, $year);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'month' => (int) $month,
            'year' => (int) $year,
            ...$report,
        ]);
    }

    public function payrollSummary(Request $request): JsonResponse
    {
        [$month,$year] = $this->getYearAndMonth($request);
        $report = $this->reportService->generatePayrollReport($month, $year);

        return response()->json([
            'month' => (int) $month,
            'year' => (int) $year,
            ...$report,
        ]);

    }

    public function coverageSummary(Request $request): JsonResponse
    {
        $date = (string) $request->query('date', now()->format('Y-m-d'));

        $report = $this->reportService->generateCoverageReport($date);

        return response()->json([
            'date' => $date,
            ...$report,

        ]);
    }

    private function getYearAndMonth(Request $request): array
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        return [$month, $year];
    }
}
